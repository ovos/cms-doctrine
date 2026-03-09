<?php

/**
 * Tests for Doctrine_Table::makeRecordInstance(), hasLoadedRecord(), getLoadedRecord()
 */
class Doctrine_Table_MakeRecordInstance_TestCase extends Doctrine_UnitTestCase
{
    public function testCreateUsesFactory()
    {
        $table = $this->connection->getTable('User');
        $record = $table->create(['name' => 'Test']);
        $this->assertTrue($record instanceof User);
        $this->assertEqual($record->name, 'Test');
        $this->assertTrue($record->state() == Doctrine_Record::STATE_TCLEAN || $record->state() == Doctrine_Record::STATE_TDIRTY);
    }

    public function testCreateEmptyArray()
    {
        $table = $this->connection->getTable('User');
        $record = $table->create([]);
        $this->assertTrue($record instanceof User);
    }

    public function testHasLoadedRecordReturnsFalseForNotLoaded()
    {
        $table = $this->connection->getTable('User');
        $this->assertFalse($table->hasLoadedRecord('999999'));
    }

    public function testGetLoadedRecordReturnsNullForNotLoaded()
    {
        $table = $this->connection->getTable('User');
        $this->assertNull($table->getLoadedRecord('999999'));
    }

    public function testIdentityMapIntegration()
    {
        $table = $this->connection->getTable('User');
        // Find an existing user — forces it into identity map
        $user = $table->find(4);
        if ($user) {
            $this->assertTrue($table->hasLoadedRecord('4'));
            $this->assertIdentical($table->getLoadedRecord('4'), $user);
        }
    }
}
