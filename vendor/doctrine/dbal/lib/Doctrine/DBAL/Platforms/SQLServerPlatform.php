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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;

/**
 * The SQLServerPlatform provides the behavior, features and SQL dialect of the
 * Microsoft SQL Server database platform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Steve Müller <st.mueller@dzh-online.de>
 */
class SQLServerPlatform extends AbstractPlatform
{
    /**
     * {@inheritDoc}
     */
    public function getDateDiffExpression($date1, $date2)
    {
        return 'DATEDIFF(day, ' . $date2 . ',' . $date1 . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateAddHourExpression($date, $hours)
    {
        return 'DATEADD(hour, ' . $hours . ', ' . $date . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateSubHourExpression($date, $hours)
    {
        return 'DATEADD(hour, -1 * ' . $hours . ', ' . $date . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateAddDaysExpression($date, $days)
    {
        return 'DATEADD(day, ' . $days . ', ' . $date . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateSubDaysExpression($date, $days)
    {
        return 'DATEADD(day, -1 * ' . $days . ', ' . $date . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateAddMonthExpression($date, $months)
    {
        return 'DATEADD(month, ' . $months . ', ' . $date . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateSubMonthExpression($date, $months)
    {
        return 'DATEADD(month, -1 * ' . $months . ', ' . $date . ')';
    }

    /**
     * {@inheritDoc}
     *
     * Microsoft SQL Server prefers "autoincrement" identity columns
     * since sequences can only be emulated with a table.
     */
    public function prefersIdentityColumns()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * Microsoft SQL Server supports this through AUTO_INCREMENT columns.
     */
    public function supportsIdentityColumns()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsReleaseSavepoints()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsSchemas()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSchemaName()
    {
        return 'dbo';
    }

    /**
     * {@inheritDoc}
     */
    public function hasNativeGuidType()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateDatabaseSQL($name)
    {
        return 'CREATE DATABASE ' . $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getDropDatabaseSQL($name)
    {
        return 'DROP DATABASE ' . $name;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsCreateDropDatabase()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateSchemaSQL($schemaName)
    {
        return 'CREATE SCHEMA ' . $schemaName;
    }

    /**
     * {@inheritDoc}
     */
    public function schemaNeedsCreation($schemaName)
    {
        return $schemaName !== 'dbo';
    }

    /**
     * {@inheritDoc}
     */
    public function getDropForeignKeySQL($foreignKey, $table)
    {
        if ($foreignKey instanceof ForeignKeyConstraint) {
            $foreignKey = $foreignKey->getQuotedName($this);
        }

        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        }

        return 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $foreignKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getDropIndexSQL($index, $table = null)
    {
        if ($index instanceof Index) {
            $index = $index->getQuotedName($this);
        } elseif (!is_string($index)) {
            throw new \InvalidArgumentException('AbstractPlatform::getDropIndexSQL() expects $index parameter to be string or \Doctrine\DBAL\Schema\Index.');
        }

        if (!isset($table)) {
            return 'DROP INDEX ' . $index;
        }

        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        }

        return "IF EXISTS (SELECT * FROM sysobjects WHERE name = '$index')
                    ALTER TABLE " . $table . " DROP CONSTRAINT " . $index . "
                ELSE
                    DROP INDEX " . $index . " ON " . $table;
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCreateTableSQL($tableName, array $columns, array $options = array())
    {
        $defaultConstraintsSql = array();
        $commentsSql           = array();

        // @todo does other code breaks because of this?
        // force primary keys to be not null
        foreach ($columns as &$column) {
            /** @var $column \Doctrine\DBAL\Schema\Column */
            if (isset($column['primary']) && $column['primary']) {
                $column['notnull'] = true;
            }

            // Build default constraints SQL statements.
            if (isset($column['default'])) {
                $defaultConstraintsSql[] = 'ALTER TABLE ' . $tableName .
                    ' ADD' . $this->getDefaultConstraintDeclarationSQL($tableName, $column);
            }

            if ( ! empty($column['comment']) || is_numeric($column['comment'])) {
                $commentsSql[] = $this->getCreateColumnCommentSQL($tableName, $column['name'], $column['comment']);
            }
        }

        $columnListSql = $this->getColumnDeclarationListSQL($columns);

        if (isset($options['uniqueConstraints']) && !empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $name => $definition) {
                $columnListSql .= ', ' . $this->getUniqueConstraintDeclarationSQL($name, $definition);
            }
        }

        if (isset($options['primary']) && !empty($options['primary'])) {
            $flags = '';
            if (isset($options['primary_index']) && $options['primary_index']->hasFlag('nonclustered')) {
                $flags = ' NONCLUSTERED';
            }
            $columnListSql .= ', PRIMARY KEY' . $flags . ' (' . implode(', ', array_unique(array_values($options['primary']))) . ')';
        }

        $query = 'CREATE TABLE ' . $tableName . ' (' . $columnListSql;

        $check = $this->getCheckDeclarationSQL($columns);
        if (!empty($check)) {
            $query .= ', ' . $check;
        }
        $query .= ')';

        $sql[] = $query;

        if (isset($options['indexes']) && !empty($options['indexes'])) {
            foreach ($options['indexes'] as $index) {
                $sql[] = $this->getCreateIndexSQL($index, $tableName);
            }
        }

        if (isset($options['foreignKeys'])) {
            foreach ((array) $options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $tableName);
            }
        }

        return array_merge($sql, $commentsSql, $defaultConstraintsSql);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatePrimaryKeySQL(Index $index, $table)
    {
        $flags = '';
        if ($index->hasFlag('nonclustered')) {
            $flags = ' NONCLUSTERED';
        }
        return 'ALTER TABLE ' . $table . ' ADD PRIMARY KEY' . $flags . ' (' . $this->getIndexFieldDeclarationListSQL($index->getQuotedColumns($this)) . ')';
    }

    /**
     * Returns the SQL statement for creating a column comment.
     *
     * SQL Server does not support native column comments,
     * therefore the extended properties functionality is used
     * as a workaround to store them.
     * The property name used to store column comments is "MS_Description"
     * which provides compatibility with SQL Server Management Studio,
     * as column comments are stored in the same property there when
     * specifying a column's "Description" attribute.
     *
     * @param string $tableName  The quoted table name to which the column belongs.
     * @param string $columnName The quoted column name to create the comment for.
     * @param string $comment    The column's comment.
     *
     * @return string
     */
    protected function getCreateColumnCommentSQL($tableName, $columnName, $comment)
    {
        return $this->getAddExtendedPropertySQL(
            'MS_Description',
            $comment,
            'SCHEMA',
            'dbo',
            'TABLE',
            $tableName,
            'COLUMN',
            $columnName
        );
    }

    /**
     * Returns the SQL snippet for declaring a default constraint.
     *
     * @param string $table  Name of the table to return the default constraint declaration for.
     * @param array  $column Column definition.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getDefaultConstraintDeclarationSQL($table, array $column)
    {
        if ( ! isset($column['default'])) {
            throw new \InvalidArgumentException("Incomplete column definition. 'default' required.");
        }

        return
            ' CONSTRAINT ' .
            $this->generateDefaultConstraintName($table, $column['name']) .
            $this->getDefaultValueDeclarationSQL($column) .
            ' FOR ' . $column['name'];
    }

    /**
     * {@inheritDoc}
     */
    public function getUniqueConstraintDeclarationSQL($name, Index $index)
    {
        $constraint = parent::getUniqueConstraintDeclarationSQL($name, $index);

        $constraint = $this->_appendUniqueConstraintDefinition($constraint, $index);

        return $constraint;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateIndexSQL(Index $index, $table)
    {
        $constraint = parent::getCreateIndexSQL($index, $table);

        if ($index->isUnique() && !$index->isPrimary()) {
            $constraint = $this->_appendUniqueConstraintDefinition($constraint, $index);
        }

        return $constraint;
    }

    /**
     * {@inheritDoc}
     */
    protected function getCreateIndexSQLFlags(Index $index)
    {
        $type = '';
        if ($index->isUnique()) {
            $type .= 'UNIQUE ';
        }

        if ($index->hasFlag('clustered')) {
            $type .= 'CLUSTERED ';
        } elseif ($index->hasFlag('nonclustered')) {
            $type .= 'NONCLUSTERED ';
        }

        return $type;
    }

    /**
     * Extend unique key constraint with required filters
     *
     * @param string                      $sql
     * @param \Doctrine\DBAL\Schema\Index $index
     *
     * @return string
     */
    private function _appendUniqueConstraintDefinition($sql, Index $index)
    {
        $fields = array();

        foreach ($index->getQuotedColumns($this) as $field) {
            $fields[] = $field . ' IS NOT NULL';
        }

        return $sql . ' WHERE ' . implode(' AND ', $fields);
    }

    /**
     * {@inheritDoc}
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        $queryParts  = array();
        $sql         = array();
        $columnSql   = array();
        $commentsSql = array();

        /** @var \Doctrine\DBAL\Schema\Column $column */
        foreach ($diff->addedColumns as $column) {
            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
                continue;
            }

            $columnDef = $column->toArray();
            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnDef);

            if (isset($columnDef['default'])) {
                $columnDef['name'] = $column->getQuotedName($this);
                $queryParts[] = 'ADD' . $this->getDefaultConstraintDeclarationSQL($diff->name, $columnDef);
            }

            $comment = $this->getColumnComment($column);

            if ( ! empty($comment) || is_numeric($comment)) {
                $commentsSql[] = $this->getCreateColumnCommentSQL(
                    $diff->name,
                    $column->getQuotedName($this),
                    $comment
                );
            }
        }

        foreach ($diff->removedColumns as $column) {
            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
                continue;
            }

            $queryParts[] = 'DROP COLUMN ' . $column->getQuotedName($this);
        }

        /* @var $columnDiff \Doctrine\DBAL\Schema\ColumnDiff */
        foreach ($diff->changedColumns as $columnDiff) {
            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
                continue;
            }

            $column     = $columnDiff->column;
            $comment    = $this->getColumnComment($column);
            $hasComment = ! empty ($comment) || is_numeric($comment);

            if ($columnDiff->fromColumn instanceof Column) {
                $fromComment    = $this->getColumnComment($columnDiff->fromColumn);
                $hasFromComment = ! empty ($fromComment) || is_numeric($fromComment);

                if ($hasFromComment && $hasComment && $fromComment != $comment) {
                    $commentsSql[] = $this->getAlterColumnCommentSQL(
                        $diff->name,
                        $column->getQuotedName($this),
                        $comment
                    );
                } elseif ($hasFromComment && ! $hasComment) {
                    $commentsSql[] = $this->getDropColumnCommentSQL($diff->name, $column->getQuotedName($this));
                } elseif ($hasComment) {
                    $commentsSql[] = $this->getCreateColumnCommentSQL(
                        $diff->name,
                        $column->getQuotedName($this),
                        $comment
                    );
                }
            } else {
                // todo: Original comment cannot be determined. What to do? Add, update, drop or skip?
            }

            // Do not add query part if only comment has changed.
            if ($columnDiff->hasChanged('comment') && count($columnDiff->changedProperties) === 1) {
                continue;
            }

            $fromColumnDefault = isset($columnDiff->fromColumn) ? $columnDiff->fromColumn->getDefault() : null;
            $columnDef = $column->toArray();
            $columnDefaultHasChanged = $columnDiff->hasChanged('default');

            /**
             * Drop existing column default constraint
             * if default value has changed and another
             * default constraint already exists for the column.
             */
            if ($columnDefaultHasChanged && null !== $fromColumnDefault) {
                $queryParts[] = 'DROP CONSTRAINT ' .
                    $this->generateDefaultConstraintName($diff->name, $columnDiff->oldColumnName);
            }

            $queryParts[] = 'ALTER COLUMN ' .
                    $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnDef);

            if ($columnDefaultHasChanged && isset($columnDef['default'])) {
                $columnDef['name'] = $column->getQuotedName($this);
                $queryParts[] = 'ADD' . $this->getDefaultConstraintDeclarationSQL($diff->name, $columnDef);
            }
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
                continue;
            }

            $sql[] = "sp_RENAME '". $diff->name. ".". $oldColumnName . "', '".$column->getQuotedName($this)."', 'COLUMN'";

            // todo: Find a way how to implement column comment alteration statements for renamed columns.

            $columnDef = $column->toArray();

            /**
             * Drop existing default constraint for the old column name
             * if column has default value.
             */
            if (isset($columnDef['default'])) {
                $queryParts[] = 'DROP CONSTRAINT ' .
                    $this->generateDefaultConstraintName($diff->name, $oldColumnName);
            }

            $queryParts[] = 'ALTER COLUMN ' .
                    $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnDef);

            /**
             * Readd default constraint for the new column name.
             */
            if (isset($columnDef['default'])) {
                $columnDef['name'] = $column->getQuotedName($this);
                $queryParts[] = 'ADD' . $this->getDefaultConstraintDeclarationSQL($diff->name, $columnDef);
            }
        }

        $tableSql = array();

        if ($this->onSchemaAlterTable($diff, $tableSql)) {
            return array_merge($tableSql, $columnSql);
        }

        foreach ($queryParts as $query) {
            $sql[] = 'ALTER TABLE ' . $diff->getName()->getQuotedName($this) . ' ' . $query;
        }

        $sql = array_merge($sql, $this->_getAlterTableIndexForeignKeySQL($diff), $commentsSql);

        if ($diff->newName !== false) {
            $sql[] = "sp_RENAME '" . $diff->getName()->getQuotedName($this) . "', '" . $diff->getNewName()->getQuotedName($this) . "'";

            /**
             * Rename table's default constraints names
             * to match the new table name.
             * This is necessary to ensure that the default
             * constraints can be referenced in future table
             * alterations as the table name is encoded in
             * default constraints' names.
             */
            $sql[] = "DECLARE @sql NVARCHAR(MAX) = N''; " .
                "SELECT @sql += N'EXEC sp_rename N''' + dc.name + ''', N''' " .
                "+ REPLACE(dc.name, '" . $this->generateIdentifierName($diff->name) . "', " .
                "'" . $this->generateIdentifierName($diff->newName) . "') + ''', ''OBJECT'';' " .
                "FROM sys.default_constraints dc " .
                "JOIN sys.tables tbl ON dc.parent_object_id = tbl.object_id " .
                "WHERE tbl.name = '" . $diff->getNewName()->getQuotedName($this) . "';" .
                "EXEC sp_executesql @sql";
        }

        return array_merge($sql, $tableSql, $columnSql);
    }

    /**
     * Returns the SQL statement for altering a column comment.
     *
     * SQL Server does not support native column comments,
     * therefore the extended properties functionality is used
     * as a workaround to store them.
     * The property name used to store column comments is "MS_Description"
     * which provides compatibility with SQL Server Management Studio,
     * as column comments are stored in the same property there when
     * specifying a column's "Description" attribute.
     *
     * @param string $tableName  The quoted table name to which the column belongs.
     * @param string $columnName The quoted column name to alter the comment for.
     * @param string $comment    The column's comment.
     *
     * @return string
     */
    protected function getAlterColumnCommentSQL($tableName, $columnName, $comment)
    {
        return $this->getUpdateExtendedPropertySQL(
            'MS_Description',
            $comment,
            'SCHEMA',
            'dbo',
            'TABLE',
            $tableName,
            'COLUMN',
            $columnName
        );
    }

    /**
     * Returns the SQL statement for dropping a column comment.
     *
     * SQL Server does not support native column comments,
     * therefore the extended properties functionality is used
     * as a workaround to store them.
     * The property name used to store column comments is "MS_Description"
     * which provides compatibility with SQL Server Management Studio,
     * as column comments are stored in the same property there when
     * specifying a column's "Description" attribute.
     *
     * @param string $tableName  The quoted table name to which the column belongs.
     * @param string $columnName The quoted column name to drop the comment for.
     *
     * @return string
     */
    protected function getDropColumnCommentSQL($tableName, $columnName)
    {
        return $this->getDropExtendedPropertySQL(
            'MS_Description',
            'SCHEMA',
            'dbo',
            'TABLE',
            $tableName,
            'COLUMN',
            $columnName
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName)
    {
        return array(
            sprintf(
                "EXEC sp_RENAME N'%s.%s', N'%s', N'INDEX'",
                $tableName,
                $oldIndexName,
                $index->getQuotedName($this)
            )
        );
    }

    /**
     * Returns the SQL statement for adding an extended property to a database object.
     *
     * @param string      $name       The name of the property to add.
     * @param string|null $value      The value of the property to add.
     * @param string|null $level0Type The type of the object at level 0 the property belongs to.
     * @param string|null $level0Name The name of the object at level 0 the property belongs to.
     * @param string|null $level1Type The type of the object at level 1 the property belongs to.
     * @param string|null $level1Name The name of the object at level 1 the property belongs to.
     * @param string|null $level2Type The type of the object at level 2 the property belongs to.
     * @param string|null $level2Name The name of the object at level 2 the property belongs to.
     *
     * @return string
     *
     * @link http://msdn.microsoft.com/en-us/library/ms180047%28v=sql.90%29.aspx
     */
    public function getAddExtendedPropertySQL(
        $name,
        $value = null,
        $level0Type = null,
        $level0Name = null,
        $level1Type = null,
        $level1Name = null,
        $level2Type = null,
        $level2Name = null
    ) {
        return "EXEC sp_addextendedproperty " .
            "N'" . $name . "', N'" . $value . "', " .
            "N'" . $level0Type . "', " . $level0Name . ', ' .
            "N'" . $level1Type . "', " . $level1Name . ', ' .
            "N'" . $level2Type . "', " . $level2Name;
    }

    /**
     * Returns the SQL statement for dropping an extended property from a database object.
     *
     * @param string      $name       The name of the property to drop.
     * @param string|null $level0Type The type of the object at level 0 the property belongs to.
     * @param string|null $level0Name The name of the object at level 0 the property belongs to.
     * @param string|null $level1Type The type of the object at level 1 the property belongs to.
     * @param string|null $level1Name The name of the object at level 1 the property belongs to.
     * @param string|null $level2Type The type of the object at level 2 the property belongs to.
     * @param string|null $level2Name The name of the object at level 2 the property belongs to.
     *
     * @return string
     *
     * @link http://technet.microsoft.com/en-gb/library/ms178595%28v=sql.90%29.aspx
     */
    public function getDropExtendedPropertySQL(
        $name,
        $level0Type = null,
        $level0Name = null,
        $level1Type = null,
        $level1Name = null,
        $level2Type = null,
        $level2Name = null
    ) {
        return "EXEC sp_dropextendedproperty " .
        "N'" . $name . "', " .
        "N'" . $level0Type . "', " . $level0Name . ', ' .
        "N'" . $level1Type . "', " . $level1Name . ', ' .
        "N'" . $level2Type . "', " . $level2Name;
    }

    /**
     * Returns the SQL statement for updating an extended property of a database object.
     *
     * @param string      $name       The name of the property to update.
     * @param string|null $value      The value of the property to update.
     * @param string|null $level0Type The type of the object at level 0 the property belongs to.
     * @param string|null $level0Name The name of the object at level 0 the property belongs to.
     * @param string|null $level1Type The type of the object at level 1 the property belongs to.
     * @param string|null $level1Name The name of the object at level 1 the property belongs to.
     * @param string|null $level2Type The type of the object at level 2 the property belongs to.
     * @param string|null $level2Name The name of the object at level 2 the property belongs to.
     *
     * @return string
     *
     * @link http://msdn.microsoft.com/en-us/library/ms186885%28v=sql.90%29.aspx
     */
    public function getUpdateExtendedPropertySQL(
        $name,
        $value = null,
        $level0Type = null,
        $level0Name = null,
        $level1Type = null,
        $level1Name = null,
        $level2Type = null,
        $level2Name = null
    ) {
        return "EXEC sp_updateextendedproperty " .
        "N'" . $name . "', N'" . $value . "', " .
        "N'" . $level0Type . "', " . $level0Name . ', ' .
        "N'" . $level1Type . "', " . $level1Name . ', ' .
        "N'" . $level2Type . "', " . $level2Name;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmptyIdentityInsertSQL($quotedTableName, $quotedIdentifierColumnName)
    {
        return 'INSERT INTO ' . $quotedTableName . ' DEFAULT VALUES';
    }

    /**
     * {@inheritDoc}
     */
    public function getListTablesSQL()
    {
        // "sysdiagrams" table must be ignored as it's internal SQL Server table for Database Diagrams
        return "SELECT name FROM sysobjects WHERE type = 'U' AND name != 'sysdiagrams' ORDER BY name";
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        return "SELECT    col.name,
                          type.name AS type,
                          col.max_length AS length,
                          ~col.is_nullable AS notnull,
                          def.definition AS [default],
                          col.scale,
                          col.precision,
                          col.is_identity AS autoincrement,
                          col.collation_name AS collation,
                          CAST(prop.value AS NVARCHAR(MAX)) AS comment -- CAST avoids driver error for sql_variant type
                FROM      sys.columns AS col
                JOIN      sys.types AS type
                ON        col.user_type_id = type.user_type_id
                JOIN      sys.objects AS obj
                ON        col.object_id = obj.object_id
                JOIN      sys.schemas AS scm
                ON        obj.schema_id = scm.schema_id
                LEFT JOIN sys.default_constraints def
                ON        col.default_object_id = def.object_id
                AND       col.object_id = def.parent_object_id
                LEFT JOIN sys.extended_properties AS prop
                ON        obj.object_id = prop.major_id
                AND       col.column_id = prop.minor_id
                AND       prop.name = 'MS_Description'
                WHERE     obj.type = 'U'
                AND       " . $this->getTableWhereClause($table, 'scm.name', 'obj.name');
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableForeignKeysSQL($table, $database = null)
    {
        return "SELECT f.name AS ForeignKey,
                SCHEMA_NAME (f.SCHEMA_ID) AS SchemaName,
                OBJECT_NAME (f.parent_object_id) AS TableName,
                COL_NAME (fc.parent_object_id,fc.parent_column_id) AS ColumnName,
                SCHEMA_NAME (o.SCHEMA_ID) ReferenceSchemaName,
                OBJECT_NAME (f.referenced_object_id) AS ReferenceTableName,
                COL_NAME(fc.referenced_object_id,fc.referenced_column_id) AS ReferenceColumnName,
                f.delete_referential_action_desc,
                f.update_referential_action_desc
                FROM sys.foreign_keys AS f
                INNER JOIN sys.foreign_key_columns AS fc
                INNER JOIN sys.objects AS o ON o.OBJECT_ID = fc.referenced_object_id
                ON f.OBJECT_ID = fc.constraint_object_id
                WHERE " .
                $this->getTableWhereClause($table, 'SCHEMA_NAME (f.schema_id)', 'OBJECT_NAME (f.parent_object_id)');
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableIndexesSQL($table, $currentDatabase = null)
    {
        return "SELECT idx.name AS key_name,
                       col.name AS column_name,
                       ~idx.is_unique AS non_unique,
                       idx.is_primary_key AS [primary],
                       CASE idx.type
                           WHEN '1' THEN 'clustered'
                           WHEN '2' THEN 'nonclustered'
                           ELSE NULL
                       END AS flags
                FROM sys.tables AS tbl
                JOIN sys.schemas AS scm ON tbl.schema_id = scm.schema_id
                JOIN sys.indexes AS idx ON tbl.object_id = idx.object_id
                JOIN sys.index_columns AS idxcol ON idx.object_id = idxcol.object_id AND idx.index_id = idxcol.index_id
                JOIN sys.columns AS col ON idxcol.object_id = col.object_id AND idxcol.column_id = col.column_id
                WHERE " . $this->getTableWhereClause($table, 'scm.name', 'tbl.name') . "
                ORDER BY idx.index_id ASC, idxcol.index_column_id ASC";
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateViewSQL($name, $sql)
    {
        return 'CREATE VIEW ' . $name . ' AS ' . $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function getListViewsSQL($database)
    {
        return "SELECT name FROM sysobjects WHERE type = 'V' ORDER BY name";
    }

    /**
     * Returns the where clause to filter schema and table name in a query.
     *
     * @param string $table        The full qualified name of the table.
     * @param string $tableColumn  The name of the column to compare the schema to in the where clause.
     * @param string $schemaColumn The name of the column to compare the table to in the where clause.
     *
     * @return string
     */
    private function getTableWhereClause($table, $schemaColumn, $tableColumn)
    {
        if (strpos($table, ".") !== false) {
            list($schema, $table) = explode(".", $table);
            $schema = "'" . $schema . "'";
        } else {
            $schema = "SCHEMA_NAME()";
        }

        return "({$tableColumn} = '{$table}' AND {$schemaColumn} = {$schema})";
    }

    /**
     * {@inheritDoc}
     */
    public function getDropViewSQL($name)
    {
        return 'DROP VIEW ' . $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getGuidExpression()
    {
        return 'NEWID()';
    }

    /**
     * {@inheritDoc}
     */
    public function getLocateExpression($str, $substr, $startPos = false)
    {
        if ($startPos == false) {
            return 'CHARINDEX(' . $substr . ', ' . $str . ')';
        }

        return 'CHARINDEX(' . $substr . ', ' . $str . ', ' . $startPos . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getModExpression($expression1, $expression2)
    {
        return $expression1 . ' % ' . $expression2;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrimExpression($str, $pos = self::TRIM_UNSPECIFIED, $char = false)
    {
        if ( ! $char) {
            switch ($pos) {
                case self::TRIM_LEADING:
                    $trimFn = 'LTRIM';
                    break;

                case self::TRIM_TRAILING:
                    $trimFn = 'RTRIM';
                    break;

                default:
                    return 'LTRIM(RTRIM(' . $str . '))';
            }

            return $trimFn . '(' . $str . ')';
        }

        /** Original query used to get those expressions
          declare @c varchar(100) = 'xxxBarxxx', @trim_char char(1) = 'x';
          declare @pat varchar(10) = '%[^' + @trim_char + ']%';
          select @c as string
          , @trim_char as trim_char
          , stuff(@c, 1, patindex(@pat, @c) - 1, null) as trim_leading
          , reverse(stuff(reverse(@c), 1, patindex(@pat, reverse(@c)) - 1, null)) as trim_trailing
          , reverse(stuff(reverse(stuff(@c, 1, patindex(@pat, @c) - 1, null)), 1, patindex(@pat, reverse(stuff(@c, 1, patindex(@pat, @c) - 1, null))) - 1, null)) as trim_both;
         */
        $pattern = "'%[^' + $char + ']%'";

        if ($pos == self::TRIM_LEADING) {
            return 'stuff(' . $str . ', 1, patindex(' . $pattern . ', ' . $str . ') - 1, null)';
        }

        if ($pos == self::TRIM_TRAILING) {
            return 'reverse(stuff(reverse(' . $str . '), 1, patindex(' . $pattern . ', reverse(' . $str . ')) - 1, null))';
        }

        return 'reverse(stuff(reverse(stuff(' . $str . ', 1, patindex(' . $pattern . ', ' . $str . ') - 1, null)), 1, patindex(' . $pattern . ', reverse(stuff(' . $str . ', 1, patindex(' . $pattern . ', ' . $str . ') - 1, null))) - 1, null))';
    }

    /**
     * {@inheritDoc}
     */
    public function getConcatExpression()
    {
        $args = func_get_args();

        return '(' . implode(' + ', $args) . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getListDatabasesSQL()
    {
        return 'SELECT * FROM SYS.DATABASES';
    }

    /**
     * {@inheritDoc}
     */
    public function getSubstringExpression($value, $from, $length = null)
    {
        if (!is_null($length)) {
            return 'SUBSTRING(' . $value . ', ' . $from . ', ' . $length . ')';
        }

        return 'SUBSTRING(' . $value . ', ' . $from . ', LEN(' . $value . ') - ' . $from . ' + 1)';
    }

    /**
     * {@inheritDoc}
     */
    public function getLengthExpression($column)
    {
        return 'LEN(' . $column . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getSetTransactionIsolationSQL($level)
    {
        return 'SET TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSQL($level);
    }

    /**
     * {@inheritDoc}
     */
    public function getIntegerTypeDeclarationSQL(array $field)
    {
        return 'INT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getBigIntTypeDeclarationSQL(array $field)
    {
        return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $field)
    {
        return 'SMALLINT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getGuidTypeDeclarationSQL(array $field)
    {
        return 'UNIQUEIDENTIFIER';
    }

    /**
     * {@inheritDoc}
     */
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed)
    {
        return $fixed ? ($length ? 'NCHAR(' . $length . ')' : 'CHAR(255)') : ($length ? 'NVARCHAR(' . $length . ')' : 'NVARCHAR(255)');
    }

    /**
     * {@inheritdoc}
     */
    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed)
    {
        return $fixed ? 'BINARY(' . ($length ?: 255) . ')' : 'VARBINARY(' . ($length ?: 255) . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaryMaxLength()
    {
        return 8000;
    }

    /**
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $field)
    {
        return 'VARCHAR(MAX)';
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
        return (!empty($columnDef['autoincrement'])) ? ' IDENTITY' : '';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATETIME';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATETIME';
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATETIME';
    }

    /**
     * {@inheritDoc}
     */
    public function getBooleanTypeDeclarationSQL(array $field)
    {
        return 'BIT';
    }

    /**
     * {@inheritDoc}
     */
    protected function doModifyLimitQuery($query, $limit, $offset = null)
    {
        if ($limit === null) {
            return $query;
        }

        $start   = $offset + 1;
        $end     = $offset + $limit;
        $orderBy = stristr($query, 'ORDER BY');
        $query   = preg_replace('/\s+ORDER\s+BY\s+([^\)]*)/', '', $query); //Remove ORDER BY from $query
        $format  = 'SELECT * FROM (%s) AS doctrine_tbl WHERE doctrine_rownum BETWEEN %d AND %d';

        if ( ! $orderBy) {
            //Replace only first occurrence of FROM with OVER to prevent changing FROM also in subqueries.
            $query = preg_replace('/\sFROM\s/i', ', ROW_NUMBER() OVER (ORDER BY (SELECT 0)) AS doctrine_rownum FROM ', $query, 1);

            return sprintf($format, $query, $start, $end);
        }

        //Clear ORDER BY
        $orderBy        = preg_replace('/ORDER\s+BY\s+([^\)]*)(.*)/', '$1', $orderBy);
        $orderByParts   = explode(',', $orderBy);
        $orderbyColumns = array();

        //Split ORDER BY into parts
        foreach ($orderByParts as &$part) {

            if (preg_match('/(([^\s]*)\.)?([^\.\s]*)\s*(ASC|DESC)?/i', trim($part), $matches)) {
                $orderbyColumns[] = array(
                    'column'    => $matches[3],
                    'hasTable'  => ( ! empty($matches[2])),
                    'sort'      => isset($matches[4]) ? $matches[4] : null,
                    'table'     => empty($matches[2]) ? '[^\.\s]*' : $matches[2]
                );
            }
        }

        $isWrapped = (preg_match('/SELECT DISTINCT .* FROM \(.*\) dctrn_result/', $query)) ? true : false;

        //Find alias for each colum used in ORDER BY
        if ( ! empty($orderbyColumns)) {
            foreach ($orderbyColumns as $column) {

                $pattern    = sprintf('/%s\.%s\s+(?:AS\s+)?([^,\s)]+)/i', $column['table'], $column['column']);

                if ($isWrapped) {
                    $overColumn = preg_match($pattern, $query, $matches)
                        ? $matches[1] : '';
                } else {
                    $overColumn = preg_match($pattern, $query, $matches)
                        ? ($column['hasTable'] ? $column['table']  . '.' : '') . $column['column']
                        : $column['column'];
                }

                if (isset($column['sort'])) {
                    $overColumn .= ' ' . $column['sort'];
                }

                $overColumns[] = $overColumn;
            }
        }

        //Replace only first occurrence of FROM with $over to prevent changing FROM also in subqueries.
        $over  = 'ORDER BY ' . implode(', ', $overColumns);
        $query = preg_replace('/\sFROM\s/i', ", ROW_NUMBER() OVER ($over) AS doctrine_rownum FROM ", $query, 1);

        return sprintf($format, $query, $start, $end);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsLimitOffset()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function convertBooleans($item)
    {
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                if (is_bool($value) || is_numeric($item)) {
                    $item[$key] = ($value) ? 1 : 0;
                }
            }
        } elseif (is_bool($item) || is_numeric($item)) {
            $item = ($item) ? 1 : 0;
        }

        return $item;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateTemporaryTableSnippetSQL()
    {
        return "CREATE TABLE";
    }

    /**
     * {@inheritDoc}
     */
    public function getTemporaryTableName($tableName)
    {
        return '#' . $tableName;
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormatString()
    {
        return 'Y-m-d H:i:s.000';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormatString()
    {
        return 'Y-m-d H:i:s.000';
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeFormatString()
    {
        return 'Y-m-d H:i:s.000';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTzFormatString()
    {
        return $this->getDateTimeFormatString();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'mssql';
    }

    /**
     * {@inheritDoc}
     */
    public function getVendor()
    {
        return 'sqlserver';
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = array(
            'bigint' => 'bigint',
            'numeric' => 'decimal',
            'bit' => 'boolean',
            'smallint' => 'smallint',
            'decimal' => 'decimal',
            'smallmoney' => 'integer',
            'int' => 'integer',
            'tinyint' => 'smallint',
            'money' => 'integer',
            'float' => 'float',
            'real' => 'float',
            'double' => 'float',
            'double precision' => 'float',
            'smalldatetime' => 'datetime',
            'datetime' => 'datetime',
            'char' => 'string',
            'varchar' => 'string',
            'text' => 'text',
            'nchar' => 'string',
            'nvarchar' => 'string',
            'ntext' => 'text',
            'binary' => 'binary',
            'varbinary' => 'binary',
            'image' => 'blob',
            'uniqueidentifier' => 'guid',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function createSavePoint($savepoint)
    {
        return 'SAVE TRANSACTION ' . $savepoint;
    }

    /**
     * {@inheritDoc}
     */
    public function releaseSavePoint($savepoint)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function rollbackSavePoint($savepoint)
    {
        return 'ROLLBACK TRANSACTION ' . $savepoint;
    }

    /**
     * {@inheritDoc}
     */
    public function appendLockHint($fromClause, $lockMode)
    {
        switch ($lockMode) {
            case LockMode::NONE:
                $lockClause = ' WITH (NOLOCK)';
                break;
            case LockMode::PESSIMISTIC_READ:
                $lockClause = ' WITH (HOLDLOCK, ROWLOCK)';
                break;
            case LockMode::PESSIMISTIC_WRITE:
                $lockClause = ' WITH (UPDLOCK, ROWLOCK)';
                break;
            default:
                $lockClause = '';
        }

        return $fromClause . $lockClause;
    }

    /**
     * {@inheritDoc}
     */
    public function getForUpdateSQL()
    {
        return ' ';
    }

    /**
     * {@inheritDoc}
     */
    protected function getReservedKeywordsClass()
    {
        return 'Doctrine\DBAL\Platforms\Keywords\SQLServerKeywords';
    }

    /**
     * {@inheritDoc}
     */
    public function quoteSingleIdentifier($str)
    {
        return "[" . str_replace("]", "][", $str) . "]";
    }

    /**
     * {@inheritDoc}
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        return 'TRUNCATE TABLE '.$tableName;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        return 'VARBINARY(MAX)';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultValueDeclarationSQL($field)
    {
        if ( ! isset($field['default'])) {
            return empty($field['notnull']) ? ' NULL' : '';
        }

        if ( ! isset($field['type'])) {
            return " DEFAULT '" . $field['default'] . "'";
        }

        if (in_array((string) $field['type'], array('Integer', 'BigInteger', 'SmallInteger'))) {
            return " DEFAULT " . $field['default'];
        }

        if ((string) $field['type'] == 'DateTime' && $field['default'] == $this->getCurrentTimestampSQL()) {
            return " DEFAULT " . $this->getCurrentTimestampSQL();
        }

        if ((string) $field['type'] == 'Boolean') {
            return " DEFAULT '" . $this->convertBooleans($field['default']) . "'";
        }

        return " DEFAULT '" . $field['default'] . "'";
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnCollationDeclarationSQL($collation)
    {
        return 'COLLATE ' . $collation;
    }

    /**
     * {@inheritdoc}
     *
     * Modifies column declaration order as it differs in Microsoft SQL Server.
     */
    public function getColumnDeclarationSQL($name, array $field)
    {
        if (isset($field['columnDefinition'])) {
            $columnDef = $this->getCustomTypeDeclarationSQL($field);
        } else {
            $collation = (isset($field['collate']) && $field['collate']) ?
                ' ' . $this->getColumnCollationDeclarationSQL($field['collate']) : '';

            $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

            $unique = (isset($field['unique']) && $field['unique']) ?
                ' ' . $this->getUniqueFieldDeclarationSQL() : '';

            $check = (isset($field['check']) && $field['check']) ?
                ' ' . $field['check'] : '';

            $typeDecl = $field['type']->getSqlDeclaration($field, $this);
            $columnDef = $typeDecl . $collation . $notnull . $unique . $check;
        }

        return $name . ' ' . $columnDef;
    }

    /**
     * Returns a unique default constraint name for a table and column.
     *
     * @param string $table  Name of the table to generate the unique default constraint name for.
     * @param string $column Name of the column in the table to generate the unique default constraint name for.
     *
     * @return string
     */
    private function generateDefaultConstraintName($table, $column)
    {
        return 'DF_' . $this->generateIdentifierName($table) . '_' . $this->generateIdentifierName($column);
    }

    /**
     * Returns a hash value for a given identifier.
     *
     * @param string $identifier Identifier to generate a hash value for.
     *
     * @return string
     */
    private function generateIdentifierName($identifier)
    {
        return strtoupper(dechex(crc32($identifier)));
    }
}
