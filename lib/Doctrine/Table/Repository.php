<?php
/*
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
 * Doctrine_Repository
 * each record is added into Doctrine_Repository at the same time they are created,
 * loaded from the database or retrieved from the cache
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @subpackage  Table
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 7490 $
 */
class Doctrine_Table_Repository implements Countable, IteratorAggregate
{
	/**
	 * @var object Doctrine_Table $table
	 */
	private $table;
	
	/**
	 * @var array<int, WeakReference> $registry
	 * weak references to all live records
	 * keys representing record object identifiers
	 *
	 * Weak references let PHP reclaim records as soon as user code drops
	 * them, instead of pinning every record ever created for the lifetime
	 * of the connection.
	 */
	private array $registry = [];
	
	/**
	 * @var array<int, Doctrine_Record> $pending
	 * strong references to records with unsaved changes (TDIRTY / DIRTY),
	 * keyed by record oid. They keep such records alive so that
	 * Doctrine_Connection::flush() can still save records that user code
	 * no longer references. Records are unpinned when they are saved,
	 * evicted or their state returns to clean.
	 */
	private array $pending = [];
	
	/**
	 * @var int $sweepThreshold
	 * dead weak references are compacted once the registry grows past this size
	 */
	private int $sweepThreshold = 1024;
	
	/**
	 * constructor
	 *
	 * @param Doctrine_Table $table
	 */
	public function __construct(Doctrine_Table $table)
	{
		$this->table = $table;
	}
	
	/**
	 * Removes entries whose records have been garbage collected and releases
	 * pins whose records are no longer dirty (self-healing for any state
	 * transition that bypassed the pin/unpin hooks).
	 *
	 * @return void
	 */
	private function sweep(): void
	{
		foreach ($this->pending as $oid => $record) {
			$state = $record->state();
			if ($state !== Doctrine_Record::STATE_TDIRTY && $state !== Doctrine_Record::STATE_DIRTY) {
				unset($this->pending[$oid]);
			}
		}
		foreach ($this->registry as $oid => $ref) {
			if ($ref->get() === null) {
				unset($this->registry[$oid]);
			}
		}
		$this->sweepThreshold = max(1024, count($this->registry) * 2);
	}
	
	/**
	 * Keeps a strong reference to a record carrying unsaved changes, so that
	 * it stays reachable for Doctrine_Connection::flush() even if user code
	 * drops it.
	 *
	 * @param Doctrine_Record $record
	 * @return void
	 */
	public function pin(Doctrine_Record $record): void
	{
		$this->pending[$record->getOid()] = $record;
	}
	
	/**
	 * Releases the strong reference kept for a record with unsaved changes.
	 *
	 * @param integer $oid      object identifier
	 * @return void
	 */
	public function unpin($oid): void
	{
		unset($this->pending[$oid]);
	}
	
	/**
	 * getTable
	 *
	 * @return Doctrine_Table
	 */
	public function getTable()
	{
		return $this->table;
	}
	
	/**
	 * add
	 *
	 * @param Doctrine_Record $record       record to be added into registry
	 * @return boolean
	 */
	public function add(Doctrine_Record $record)
	{
		$oid = $record->getOID();
		
		if (isset($this->registry[$oid]) && $this->registry[$oid]->get() !== null) {
			return false;
		}
		
		if (count($this->registry) >= $this->sweepThreshold) {
			$this->sweep();
		}
		
		$this->registry[$oid] = WeakReference::create($record);
		
		$state = $record->state();
		if ($state === Doctrine_Record::STATE_TDIRTY || $state === Doctrine_Record::STATE_DIRTY) {
			$this->pending[$oid] = $record;
		}
		
		return true;
	}
	
	/**
	 * get
	 * @param integer $oid
	 * @throws Doctrine_Table_Repository_Exception
	 */
	public function get($oid)
	{
		$record = isset($this->registry[$oid]) ? $this->registry[$oid]->get() : null;
		if ($record === null) {
			unset($this->registry[$oid]);
			throw new Doctrine_Table_Repository_Exception("Unknown object identifier");
		}
		return $record;
	}
	
	/**
	 * count
	 * Doctrine_Registry implements interface Countable
	 * @return integer                      the number of records this registry has
	 */
	public function count(): int
	{
		$this->sweep();
		return count($this->registry);
	}
	
	/**
	 * @param integer $oid                  object identifier
	 * @return boolean                      whether ot not the operation was successful
	 */
	public function evict($oid)
	{
		unset($this->pending[$oid]);
		if ( ! isset($this->registry[$oid])) {
			return false;
		}
		$live = $this->registry[$oid]->get() !== null;
		unset($this->registry[$oid]);
		return $live;
	}
	
	/**
	 * @return integer                      number of records evicted
	 */
	public function evictAll()
	{
		$evicted = 0;
		foreach ($this->registry as $oid => $ref) {
			if ($ref->get() !== null) {
				$evicted++;
			}
		}
		$this->registry = [];
		$this->pending = [];
		return $evicted;
	}
	
	/**
	 * getIterator
	 * @return ArrayIterator
	 */
	public function getIterator(): \Iterator
	{
		$records = [];
		foreach ($this->registry as $oid => $ref) {
			$record = $ref->get();
			if ($record !== null) {
				$records[$oid] = $record;
			} else {
				unset($this->registry[$oid]);
			}
		}
		return new ArrayIterator($records);
	}
	
	/**
	 * contains
	 * @param integer $oid                  object identifier
	 */
	public function contains($oid)
	{
		return isset($this->registry[$oid]) && $this->registry[$oid]->get() !== null;
	}
	
	/**
	 * loadAll
	 * @return void
	 */
	public function loadAll()
	{
		$this->table->findAll();
	}
}
