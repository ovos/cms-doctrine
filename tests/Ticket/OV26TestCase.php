<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Doctrine_Ticket_OV26_TestCase
 *
 * Memory-leak fixes: the identity map and the table repository hold weak
 * references, so records become garbage collectible as soon as user code
 * drops them. Records with unsaved changes stay strongly pinned so that
 * Doctrine_Connection::flush() can still reach them. Also covers the
 * hydrator fast paths that must not change visible behavior (duplicate
 * row deduplication, Doctrine_Overloadable record listeners).
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Ticket_OV26_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables[] = 'Ticket_OV26_User';
        $this->tables[] = 'Ticket_OV26_Phone';
        parent::prepareTables();
    }

    public function prepareData()
    {
        $user = new Ticket_OV26_User();
        $user->name = 'alice';
        $user->save();

        foreach (['111', '222'] as $number) {
            $phone = new Ticket_OV26_Phone();
            $phone->user_id = $user->id;
            $phone->number = $number;
            $phone->save();
        }
    }

    public function testHydratedRecordsAreCollectibleOnceDropped()
    {
        $users = Doctrine_Query::create($this->conn)
            ->from('Ticket_OV26_User u')
            ->execute();

        $probe = WeakReference::create($users[0]);

        unset($users);
        gc_collect_cycles();

        $this->assertNull($probe->get());
    }

    public function testSavedRecordsAreCollectibleOnceDropped()
    {
        $phone = new Ticket_OV26_Phone();
        $phone->user_id = 1;
        $phone->number = '333';
        $phone->save();

        $probe = WeakReference::create($phone);

        unset($phone);
        gc_collect_cycles();

        $this->assertNull($probe->get());
    }

    public function testIdentityMapStillReturnsSameInstanceWhileAlive()
    {
        $first = $this->conn->getTable('Ticket_OV26_User')->find(1);
        $second = $this->conn->getTable('Ticket_OV26_User')->find(1);

        $this->assertTrue($first === $second);
    }

    public function testFlushSavesDereferencedNewRecords()
    {
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_ov26_phone');

        for ($i = 0; $i < 3; $i++) {
            $phone = new Ticket_OV26_Phone();
            $phone->user_id = 1;
            $phone->number = 'flush-' . $i;
            unset($phone); // drop the only user-land reference before flush
        }
        gc_collect_cycles();

        $this->conn->flush();

        $after = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_ov26_phone');
        $this->assertEqual($after - $before, 3);
    }

    public function testFlushSavesDereferencedDirtyRecords()
    {
        $this->conn->getTable('Ticket_OV26_User')->find(1)->set('name', 'bob');
        gc_collect_cycles();

        $this->conn->flush();
        $this->conn->getTable('Ticket_OV26_User')->clear();

        $name = $this->conn->fetchOne('SELECT name FROM ticket_ov26_user WHERE id = 1');
        $this->assertEqual($name, 'bob');
    }

    public function testOverloadableRecordListenerIsInvokedDuringHydration()
    {
        $table = $this->conn->getTable('Ticket_OV26_User');
        $original = $table->getRecordListener();

        $listener = new Ticket_OV26_OverloadableListener();
        $table->setAttribute(Doctrine_Core::ATTR_RECORD_LISTENER, $listener);

        try {
            $users = Doctrine_Query::create($this->conn)
                ->from('Ticket_OV26_User u')
                ->execute();

            $this->assertTrue(count($users) > 0);
            $this->assertTrue(($listener->calls['preHydrate'] ?? 0) > 0);
            $this->assertTrue(($listener->calls['postHydrate'] ?? 0) > 0);
        } finally {
            $table->setAttribute(Doctrine_Core::ATTR_RECORD_LISTENER, $original);
        }
    }

    public function testDiamondJoinDoesNotDuplicateRelationEntries()
    {
        $this->conn->getTable('Ticket_OV26_User')->clear();
        $this->conn->getTable('Ticket_OV26_Phone')->clear();

        $expected = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_ov26_phone WHERE user_id = 1');

        $users = Doctrine_Query::create($this->conn)
            ->from('Ticket_OV26_User u')
            ->leftJoin('u.Phones p')
            ->leftJoin('p.User u2')
            ->leftJoin('u2.Phones p2')
            ->where('u.id = 1')
            ->execute();

        $this->assertEqual(count($users[0]->Phones), $expected);
    }

    public function testRawSqlWithMultipliedRootRowsDeduplicates()
    {
        $q = new Doctrine_RawSql($this->conn);
        $users = $q->select('{u.*}')
            ->from('ticket_ov26_user u LEFT JOIN ticket_ov26_phone p ON u.id = p.user_id')
            ->addComponent('u', 'Ticket_OV26_User u')
            ->where('u.id = 1')
            ->execute();

        $this->assertEqual(count($users), 1);
    }
}

class Ticket_OV26_User extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('ticket_ov26_user');
        $this->hasColumn('name', 'string', 100);
    }

    public function setUp()
    {
        $this->hasMany('Ticket_OV26_Phone as Phones', array('local' => 'id', 'foreign' => 'user_id'));
    }
}

class Ticket_OV26_Phone extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('ticket_ov26_phone');
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('number', 'string', 30);
    }

    public function setUp()
    {
        $this->hasOne('Ticket_OV26_User as User', array('local' => 'user_id', 'foreign' => 'id'));
    }
}

class Ticket_OV26_OverloadableListener implements Doctrine_Overloadable
{
    public $calls = array();

    public function __call($method, $args)
    {
        $this->calls[$method] = ($this->calls[$method] ?? 0) + 1;
    }
}
