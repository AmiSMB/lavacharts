<?php

namespace Khill\Lavacharts\Tests\DataTables;

use Khill\Lavacharts\DataTables\Columns\ColumnFactory;
use Khill\Lavacharts\DataTables\DataTable;
use Khill\Lavacharts\Tests\ProvidersTestCase;
use Carbon\Carbon;

class DataTableTest extends ProvidersTestCase
{
    /**
     * @var \Khill\Lavacharts\DataTables\DataTable
     */
    public $DataTable;

    public $columnTypes = [
        'BooleanColumn',
        'NumberColumn',
        'StringColumn',
        'DateColumn',
        'DateTimeColumn',
        'TimeOfDayColumn'
    ];

    public $columnLabels = [
        'tooltip',
        'Admin',
        'Unique Visitors',
        'People In Group',
        'Most Commits',
        'Entries Edited',
        'Peak Usage Hours'
    ];

    public $tzLA = 'America/Los_Angeles';

    public $tzNY = 'America/New_York';

    public function setUp()
    {
        parent::setUp();

        date_default_timezone_set($this->tzLA);

        $this->DataTable = new DataTable();
    }

    public function createMock($class)
    {
        return \Mockery::mock(DATATABLE_NS.$class);
    }

    public function privateColumnAccess($index = null)
    {
        $cols = $this->inspect($this->DataTable, 'cols');

        return is_int($index) ? $cols[$index] : $cols;
    }

    public function privateRowAccess($index = null)
    {
        $rows = $this->inspect($this->DataTable, 'rows');

        return is_int($index) ? $rows[$index] : $rows;
    }

    public function privateCellAccess($rowIndex, $cellIndex)
    {
        $row = $this->privateRowAccess($rowIndex);

        return $row[$cellIndex];
    }

    public function columnCreationNameProvider()
    {
        return array_map(function ($columnName) {
            return [$columnName];
        }, $this->columnTypes);
    }

    public function columnTypeAndLabelProvider()
    {
        $columns = [];

        foreach (ColumnFactory::$types as $index => $type) {
            $columns[] = [$type, $this->columnLabels[$index]];
        }

        return $columns;
    }

    public function testDefaultTimezoneUponCreation()
    {
        $tz = $this->inspect($this->DataTable, 'timezone');

        $this->assertEquals($this->tzLA, $tz->getName());
    }

    public function testSetTimezoneWithConstructor()
    {
        $this->markTestSkipped('Removing timezone setting from constructor.');

        $datatable = new DataTable($this->tzNY);

        $tz = $this->inspect($datatable, 'timezone');

        $this->assertEquals($this->tzNY, $tz->getName());
    }

    public function testSetTimezoneMethod()
    {
        $this->DataTable->setTimezone($this->tzNY);

        $tz = $this->inspect($this->DataTable, 'timezone');

        $this->assertEquals($this->tzNY, $tz->getName());
    }

    /**
     * @depends testSetTimezoneMethod
     * @dataProvider nonStringProvider
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidTimeZone
     */
    public function testSetTimezoneWithBadType($badTypes)
    {
        $this->DataTable->setTimezone($badTypes);
    }

    /**
     * @depends testSetTimezoneMethod
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidTimeZone
     */
    public function testSetTimezoneWithInvalidTimezone($badTypes)
    {
        $this->DataTable->setTimezone('Murica');
    }

    /**
     * @depends testSetTimezoneMethod
     */
    public function testGetTimezoneMethod()
    {
        $this->DataTable->setTimezone($this->tzNY);

        $this->assertInstanceOf('DateTimeZone', $this->DataTable->getTimezone());
        $this->assertEquals($this->tzNY, $this->DataTable->getTimezone()->getName());
    }

    public function testSetDateTimeFormat()
    {
        $this->DataTable->setDateTimeFormat('YYYY-mm-dd');

        $format = $this->inspect($this->DataTable, 'dateTimeFormat');

        $this->assertEquals('YYYY-mm-dd', $format);
    }

    /**
     * @depends testSetDateTimeFormat
     * @dataProvider nonStringProvider
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidDateTimeFormat
     */
    public function testSetDateTimeFormatWithBadTypes($badTypes)
    {
        $this->DataTable->setDateTimeFormat($badTypes);
    }

    /**
     * @depends testSetDateTimeFormat
     */
    public function testGetDateTimeFormat()
    {
        $this->DataTable->setDateTimeFormat('YYYY-mm-dd');

        $this->assertEquals('YYYY-mm-dd', $this->DataTable->getDateTimeFormat());
    }

    /**
     * @dataProvider columnTypeProvider
     */
    public function testAddColumnByType($columnType)
    {
        $this->DataTable->addColumn($columnType);

        $column = $this->privateColumnAccess(0);

        $this->assertEquals($columnType, $column->getType());
    }

    /**
     * @depends testAddColumnByType
     * @dataProvider columnTypeProvider
     */
    public function testAddColumnByTypeInArray($columnType)
    {
        $this->DataTable->addColumn([$columnType]);

        $column = $this->privateColumnAccess(0);

        $this->assertEquals($columnType, $column->getType());
    }

    /**
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidConfigValue
     */
    public function testAddColumnWithBadTypes()
    {
        $this->DataTable->addColumn(1);
        $this->DataTable->addColumn(1.1);
        $this->DataTable->addColumn(false);
        $this->DataTable->addColumn(new \stdClass());
    }

    /**
     * @dataProvider columnCreationNameProvider
     * @covers \Khill\Lavacharts\DataTables\DataTable::addBooleanColumn
     * @covers \Khill\Lavacharts\DataTables\DataTable::addStringColumn
     * @covers \Khill\Lavacharts\DataTables\DataTable::addNumberColumn
     * @covers \Khill\Lavacharts\DataTables\DataTable::addDateColumn
     * @covers \Khill\Lavacharts\DataTables\DataTable::addDateTimeColumn
     * @covers \Khill\Lavacharts\DataTables\DataTable::addTimeOfDayColumn
     */
    public function testAddColumnViaNamedAlias($columnType)
    {
        call_user_func([$this->DataTable, 'add' . $columnType]);

        $column = $this->privateColumnAccess(0);

        $type = strtolower(str_replace('Column', '', $columnType));

        $this->assertEquals($type, $column->getType());
    }

    /**
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidColumnDefinition
     * @covers \Khill\Lavacharts\DataTables\DataTable::addColumns
     */
    public function testAddColumnsWithBadTypesInArray()
    {
        $this->DataTable->addColumns([
            5.6,
            15.6244,
            'hotdogs'
        ]);
    }

    /**
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidColumnType
     * @covers \Khill\Lavacharts\DataTables\DataTable::addColumns
     */
    public function testAddColumnsWithBadValuesInArray()
    {
        $this->DataTable->addColumns([
            [5, 'falcons'],
            ['tacos', false]
        ]);
    }

    /**
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRoleColumn
     */
    public function testAddRoleColumn()
    {
        $this->DataTable->addRoleColumn('number', 'interval');

        $column = $this->privateColumnAccess(0);

        $this->assertEquals('number', $column->getType());
        $this->assertEquals('interval', $column->getRole());
    }

    /**
     * @dataProvider nonStringProvider
     * @depends testAddRoleColumn
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRoleColumn
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidColumnType
     */
    public function testAddRoleColumnWithBadColumnTypes($badTypes)
    {
        $this->DataTable->addRoleColumn($badTypes, 'interval');
    }

    /**
     * @dataProvider nonStringProvider
     * depends testAddRoleColumn
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRoleColumn
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidColumnRole
     */
    public function testAddRoleColumnWithBadRoleTypes($badTypes)
    {
        $this->DataTable->addRoleColumn('number', $badTypes);
    }

    /**
     * @depends testAddRoleColumn
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRoleColumn
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidColumnRole
     */
    public function testAddRoleColumnWithBadRoleValue()
    {
        $this->DataTable->addRoleColumn('number', 'stairs');
    }

    /**
     * @depends testAddColumnViaNamedAlias
     */
    public function testDropColumnWithIndex()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addStringColumn();

        $columns = $this->privateColumnAccess();

        $this->assertEquals(3, count($columns));
        $this->assertEquals('number', $columns[1]->getType());

        $this->DataTable->dropColumn(1);
        $columns = $this->privateColumnAccess();
        $this->assertEquals(2, count($columns));
        $this->assertEquals('string', $columns[1]->getType());

        $this->DataTable->dropColumn(1);
        $columns = $this->privateColumnAccess();
        $this->assertEquals(1, count($columns));
        $this->assertFalse(isset($columns[1]));
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @dataProvider nonIntProvider
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidColumnIndex
     */
    public function testDropColumnWithBadType($badTypes)
    {
        $this->DataTable->addNumberColumn();

        $this->DataTable->dropColumn($badTypes);
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidColumnIndex
     * @covers \Khill\Lavacharts\DataTables\DataTable::dropColumn
     */
    public function testDropColumnWithNonExistentIndex()
    {
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $this->DataTable->dropColumn(4);
    }

    /**
     * @depends testAddColumnByType
     * @dataProvider columnTypeAndLabelProvider
     * @covers \Khill\Lavacharts\DataTables\DataTable::addColumn
     */
    public function testAddColumnWithTypeAndLabel($columnType, $columnLabel)
    {
        $this->DataTable->addColumn($columnType, $columnLabel);

        $column = $this->privateColumnAccess(0);

        $this->assertEquals($columnType, $column->getType());
        $this->assertEquals($columnLabel, $column->getLabel());
    }

    /**
     * @covers \Khill\Lavacharts\DataTables\DataTable::addColumn
     */
    public function testAddColumnWithArrayOfTypeAndLabel()
    {
        $this->DataTable->addColumn(['date', 'Days in March']);

        $column = $this->privateColumnAccess(0);

        $this->assertEquals('date', $column->getType());
        $this->assertEquals('Days in March', $column->getLabel());
    }

    /**
     * @covers \Khill\Lavacharts\DataTables\DataTable::addColumns
     */
    public function testAddColumnsWithArrayOfTypeAndLabel()
    {
        $this->DataTable->addColumns([
            ['date', 'Days in March'],
            ['number', 'Day of the Week'],
            ['number', 'Temperature'],
        ]);

        $columns = $this->privateColumnAccess();

        $this->assertEquals('date', $columns[0]->getType());
        $this->assertEquals('Days in March', $columns[0]->getLabel());

        $this->assertEquals('number', $columns[1]->getType());
        $this->assertEquals('Day of the Week', $columns[1]->getLabel());

        $this->assertEquals('number', $columns[2]->getType());
        $this->assertEquals('Temperature', $columns[2]->getLabel());
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRow
     */
    public function testAddRowWithEmptyArrayForNull()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addRow([]);

        $row = $this->privateRowAccess(0);

        $this->assertNull($this->inspect($row, 'values')[0]->getValue());
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRow
     */
    public function testAddRowWithNull()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addRow(null);

        $row = $this->privateRowAccess(0);

        $this->assertNull($this->inspect($row, 'values')[0]->getValue());
    }

    /**
     * depends testAddColumnViaNamedAlias
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRow
     */
    public function testAddRowWithDate()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addRow([Carbon::parse('March 24th, 1988')]);

        $column = $this->privateColumnAccess(0);
        $cell   = $this->privateCellAccess(0, 0);

        $this->assertEquals('date', $column->getType());
        $this->assertInstanceOf('\Khill\Lavacharts\Datatables\Cells\DateCell', $cell);
        $this->assertEquals('Date(1988,2,24,0,0,0)', (string) $cell);
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRow
     */
    public function testAddRowWithMultipleColumnsWithDateAndNumbers()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $this->DataTable->addRow([Carbon::parse('March 24th, 1988'), 12345, 67890]);

        $columns = $this->privateColumnAccess();
        $row     = $this->privateRowAccess(0);

        $this->assertEquals('date', $columns[0]->getType());
        $this->assertEquals('number', $columns[1]->getType());
        $this->assertEquals('number', $columns[2]->getType());

        $this->assertEquals('Date(1988,2,24,0,0,0)', $row->getCell(0));
        $this->assertEquals(12345, $row->getCell(1)->getValue());
        $this->assertEquals(67890, $row->getCell(2)->getValue());
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRows
     */
    public function testAddRowsWithMultipleColumnsWithDateAndNumbers()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $rows = [
            [Carbon::parse('March 24th, 1988'), 12345, 67890],
            [Carbon::parse('March 25th, 1988'), 1122, 3344]
        ];

        $this->DataTable->addRows($rows);

        $columns = $this->privateColumnAccess();
        $rows    = $this->privateRowAccess();

        $this->assertEquals('date', $columns[0]->getType());
        $this->assertEquals('number', $columns[1]->getType());
        $this->assertEquals('number', $columns[2]->getType());

        $this->assertEquals('Date(1988,2,24,0,0,0)', (string) $rows[0]->getCell(0));
        $this->assertEquals(12345, $rows[0]->getCell(1)->getValue());
        $this->assertEquals(67890, $rows[0]->getCell(2)->getValue());

        $this->assertEquals('Date(1988,2,25,0,0,0)', (string) $rows[1]->getCell(0));
        $this->assertEquals(1122, $rows[1]->getCell(1)->getValue());
        $this->assertEquals(3344, $rows[1]->getCell(2)->getValue());
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidCellCount
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRow
     */
    public function testAddRowWithMoreCellsThanColumns()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();

        $this->DataTable->addRow([Carbon::parse('March 24th, 1988'), 12345, 67890]);
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @dataProvider nonCarbonOrDateStringProvider
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidDate
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRow
     */
    public function testAddRowWithBadDateTypes($badDate)
    {
        $this->DataTable->addDateColumn();

        $this->DataTable->addRow([$badDate]);
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @covers \Khill\Lavacharts\DataTables\DataTable::addRow
     */
    public function testAddRowWithEmptyArray()
    {
        $this->DataTable->addDateColumn();

        $this->DataTable->addRow([]);
    }

    /**
     * @depends testAddRowsWithMultipleColumnsWithDateAndNumbers
     * @covers \Khill\Lavacharts\DataTables\DataTable::getRows
     */
    public function testGetRows()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $rows = [
            [Carbon::parse('March 24th, 1988'), 12345, 67890],
            [Carbon::parse('March 25th, 1988'), 1122, 3344]
        ];

        $this->DataTable->addRows($rows);

        $rows = $this->DataTable->getRows();

        $this->assertInstanceOf(DATATABLE_NS.'Rows\Row', $rows[0]);
        $this->assertInstanceOf(DATATABLE_NS.'Rows\Row', $rows[1]);
    }

    /**
     * @depends testGetRows
     * @covers \Khill\Lavacharts\DataTables\DataTable::getRowCount
     */
    public function testGetRowCount()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $rows = [
            [Carbon::parse('March 24th, 1988'), 12345, 67890],
            [Carbon::parse('March 25th, 1988'), 1122, 3344]
        ];

        $this->DataTable->addRows($rows);

        $this->assertEquals(2, $this->DataTable->getRowCount());
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @covers \Khill\Lavacharts\DataTables\DataTable::formatColumn
     */
    public function testFormatColumn()
    {
        $mockDateFormat = $this->createMock('Formats\DateFormat');

        $this->DataTable->addDateColumn();

        $this->DataTable->formatColumn(0, $mockDateFormat);

        $column = $this->privateColumnAccess(0);

        $this->assertInstanceOf(
            DATATABLE_NS.'Formats\DateFormat',
            $this->inspect($column, 'format')
        );
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @depends testFormatColumn
     * @expectedException \Khill\Lavacharts\Exceptions\InvalidColumnIndex
     * @covers \Khill\Lavacharts\DataTables\DataTable::formatColumn
     */
    public function testFormatColumnWithBadIndex()
    {
        $mockDateFormat = $this->createMock('Formats\DateFormat');

        $this->DataTable->addDateColumn();

        $this->DataTable->formatColumn(672, $mockDateFormat);
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @depends testFormatColumn
     * @covers \Khill\Lavacharts\DataTables\DataTable::formatColumns
     */
    public function testFormatColumns()
    {
        $mockDateFormat = $this->createMock('Formats\DateFormat');
        $mockNumberFormat = $this->createMock('Formats\NumberFormat');

        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $this->DataTable->formatColumns([
            0 => $mockDateFormat,
            2 => $mockNumberFormat
        ]);

        $columns = $this->privateColumnAccess();

        $this->assertInstanceOf(
            DATATABLE_NS.'Formats\DateFormat',
            $this->inspect($columns[0], 'format')
        );

        $this->assertInstanceOf(
            DATATABLE_NS.'Formats\NumberFormat',
            $this->inspect($columns[2], 'format')
        );
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @depends testFormatColumns
     * @covers \Khill\Lavacharts\DataTables\DataTable::getFormattedColumns
     */
    public function testGetFormattedColumns()
    {
        $mockDateFormat = $this->createMock('Formats\DateFormat');
        $mockNumberFormat = $this->createMock('Formats\NumberFormat');

        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $this->DataTable->formatColumns([
            0 => $mockDateFormat,
            2 => $mockNumberFormat
        ]);

        $columns = $this->DataTable->getFormattedColumns();

        $this->assertInstanceOf(
            DATATABLE_NS.'Formats\DateFormat',
            $this->inspect($columns[0], 'format')
        );

        $this->assertInstanceOf(
            DATATABLE_NS.'Formats\NumberFormat',
            $this->inspect($columns[2], 'format')
        );
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @depends testGetFormattedColumns
     * @covers \Khill\Lavacharts\DataTables\DataTable::hasFormattedColumns
     */
    public function testHasFormattedColumns()
    {
        $mockDateFormat = $this->createMock('Formats\DateFormat');
        $mockNumberFormat = $this->createMock('Formats\NumberFormat');

        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $this->DataTable->formatColumns([
            0 => $mockDateFormat,
            2 => $mockNumberFormat
        ]);

        $this->assertTrue($this->DataTable->hasFormattedColumns());
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @depends testGetFormattedColumns
     * @covers \Khill\Lavacharts\DataTables\DataTable::hasFormattedColumns
     */
    public function testHasFormattedColumnsWithNoFormattedColumns()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $this->assertFalse($this->DataTable->hasFormattedColumns());
    }

     /**
     * @depends testAddColumnWithTypeAndLabel
     */
    public function testAddRowsWithMultipleColumnsWithDateTimeAndNumbers()
    {
        $this->DataTable->addColumns([
            ['datetime'],
            ['number'],
            ['number']
        ])->addRows([
            [Carbon::parse('March 24th, 1988 8:01:05'), 12345, 67890],
            [Carbon::parse('March 25th, 1988 8:02:06'), 1122, 3344]
        ]);

        $columns = $this->privateColumnAccess();
        $rows    = $this->privateRowAccess();

        $this->assertEquals('datetime', $columns[0]->getType());
        $this->assertEquals('number', $columns[1]->getType());
        $this->assertEquals('number', $columns[2]->getType());

        $this->assertEquals('Date(1988,2,24,8,1,5)', $rows[0]->getCell(0));
        $this->assertEquals(12345, $rows[0]->getCell(1)->getValue());
        $this->assertEquals(67890, $rows[0]->getCell(2)->getValue());

        $this->assertEquals('Date(1988,2,25,8,2,6)', $rows[1]->getCell(0));
        $this->assertEquals(1122, $rows[1]->getCell(1)->getValue());
        $this->assertEquals(3344, $rows[1]->getCell(2)->getValue());
    }

    /**
     * @depends testAddColumnWithTypeAndLabel
     * @covers \Khill\Lavacharts\DataTables\DataTable::getColumn
     */
    public function testGetColumn()
    {
        $this->DataTable->addColumn('date', 'Test1');
        $this->DataTable->addColumn('number', 'Test2');

        $column = $this->DataTable->getColumn(1);

        $this->assertInstanceOf(DATATABLE_NS.'Columns\Column', $column);
        $this->assertEquals('Test2', $this->inspect($column, 'label'));
    }

    /**
     * @depends testAddColumnWithTypeAndLabel
     */
    public function testGetColumns()
    {
        $this->DataTable->addColumn('date', 'Test1');
        $this->DataTable->addColumn('number', 'Test2');

        $columns = $this->DataTable->getColumns();

        $this->assertTrue(is_array($columns));
        $this->assertInstanceOf(DATATABLE_NS.'Columns\Column', $columns[0]);
        $this->assertInstanceOf(DATATABLE_NS.'Columns\Column', $columns[1]);
    }

    /**
     * @depends testAddColumnWithTypeAndLabel
     * @covers \Khill\Lavacharts\DataTables\DataTable::getColumnLabel
     */
    public function testGetColumnLabel()
    {
        $this->DataTable->addColumn('date', 'Test1');
        $this->DataTable->addColumn('number', 'Test2');

        $this->assertEquals('Test2', $this->DataTable->getColumnLabel(1));
    }

    /**
     * @depends testAddColumnWithTypeAndLabel
     * @covers \Khill\Lavacharts\DataTables\DataTable::getColumnLabels
     */
    public function testGetColumnLabels()
    {
        $this->DataTable->addColumn('date', 'Test1');
        $this->DataTable->addColumn('number', 'Test2');

        $labels = $this->DataTable->getColumnLabels();

        $this->assertTrue(is_array($labels));
        $this->assertEquals('Test1', $labels[0]);
        $this->assertEquals('Test2', $labels[1]);
    }

    /**
     * @depends testAddColumnWithTypeAndLabel
     * @covers \Khill\Lavacharts\DataTables\DataTable::getColumnType
     */
    public function testGetColumnType()
    {
        $this->DataTable->addColumn('date', 'Test1');
        $this->DataTable->addColumn('number', 'Test2');

        $this->assertEquals('date', $this->DataTable->getColumnType(0));
    }

    /**
     * @depends testAddColumnByType
     * @dataProvider columnTypeProvider
     */
    public function testGetColumnTypeWithIndex($type)
    {
        $this->DataTable->addColumn($type);

        $this->assertEquals($type, $this->DataTable->getColumnType(0));
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @covers \Khill\Lavacharts\DataTables\DataTable::getColumnsByType
     */
    public function testGetColumnsByType()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();

        $this->assertEquals([0], array_keys($this->DataTable->getColumnsByType('date')));
        $this->assertEquals([1], array_keys($this->DataTable->getColumnsByType('number')));
    }

    /**
     * @depends testAddColumnViaNamedAlias
     * @covers \Khill\Lavacharts\DataTables\DataTable::getColumnsByType
     */
    public function testGetColumnsByTypeWithDuplicateTypes()
    {
        $this->DataTable->addDateColumn();
        $this->DataTable->addNumberColumn();
        $this->DataTable->addNumberColumn();

        $this->assertTrue(is_array($this->DataTable->getColumnsByType('number')));
        $this->assertEquals([1,2], array_keys($this->DataTable->getColumnsByType('number')));
    }

    /**
     * @depends testAddColumnByType
     * @depends testAddRowsWithMultipleColumnsWithDateAndNumbers
     * @depends testGetRowCount
     */
    public function testBare()
    {
        $this->DataTable->addNumberColumn('num')->addRows([[1],[2],[3],[4]]);

        $this->assertEquals($this->DataTable->getRowCount(), 4);

        $this->assertEquals($this->DataTable->bare()->getRowCount(), 0);
    }
}
