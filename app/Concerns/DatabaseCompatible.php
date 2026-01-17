<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Provides database-agnostic query helpers for MySQL/SQLite compatibility.
 *
 * Use these methods instead of raw SQL to ensure queries work in both
 * development (SQLite) and production (MySQL) environments.
 */
trait DatabaseCompatible
{
    /**
     * Get the database driver name.
     */
    protected function getDatabaseDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Check if using SQLite.
     */
    protected function isUsingSqlite(): bool
    {
        return $this->getDatabaseDriver() === 'sqlite';
    }

    /**
     * Check if using MySQL.
     */
    protected function isUsingMysql(): bool
    {
        return $this->getDatabaseDriver() === 'mysql';
    }

    /**
     * Get date format expression compatible with both MySQL and SQLite.
     *
     * @param  string  $column  The date column name
     * @param  string  $format  The format (use PHP date format, will be converted)
     */
    protected function dateFormat(string $column, string $format): string
    {
        if ($this->isUsingSqlite()) {
            // Convert PHP date format to SQLite strftime format
            $sqliteFormat = $this->convertToSqliteFormat($format);

            return "strftime('{$sqliteFormat}', {$column})";
        }

        // MySQL DATE_FORMAT
        $mysqlFormat = $this->convertToMysqlFormat($format);

        return "DATE_FORMAT({$column}, '{$mysqlFormat}')";
    }

    /**
     * Get year from date - cross-database compatible.
     */
    protected function yearFromDate(string $column): string
    {
        if ($this->isUsingSqlite()) {
            return "strftime('%Y', {$column})";
        }

        return "YEAR({$column})";
    }

    /**
     * Get month from date - cross-database compatible.
     */
    protected function monthFromDate(string $column): string
    {
        if ($this->isUsingSqlite()) {
            return "strftime('%m', {$column})";
        }

        return "MONTH({$column})";
    }

    /**
     * Get day from date - cross-database compatible.
     */
    protected function dayFromDate(string $column): string
    {
        if ($this->isUsingSqlite()) {
            return "strftime('%d', {$column})";
        }

        return "DAY({$column})";
    }

    /**
     * Get current timestamp - cross-database compatible.
     */
    protected function currentTimestamp(): string
    {
        if ($this->isUsingSqlite()) {
            return "datetime('now')";
        }

        return 'NOW()';
    }

    /**
     * Get current date - cross-database compatible.
     */
    protected function currentDate(): string
    {
        if ($this->isUsingSqlite()) {
            return "date('now')";
        }

        return 'CURDATE()';
    }

    /**
     * Coalesce/IFNULL - use COALESCE which works on both.
     *
     * @param  array<string>  $columns
     */
    protected function coalesce(array $columns): string
    {
        return 'COALESCE(' . implode(', ', $columns) . ')';
    }

    /**
     * Concatenate strings - cross-database compatible.
     *
     * @param  array<string>  $values
     */
    protected function concat(array $values): string
    {
        if ($this->isUsingSqlite()) {
            return implode(' || ', $values);
        }

        return 'CONCAT(' . implode(', ', $values) . ')';
    }

    /**
     * Group concatenate - cross-database compatible.
     */
    protected function groupConcat(string $column, string $separator = ','): string
    {
        if ($this->isUsingSqlite()) {
            return "GROUP_CONCAT({$column}, '{$separator}')";
        }

        return "GROUP_CONCAT({$column} SEPARATOR '{$separator}')";
    }

    /**
     * Date difference in days - cross-database compatible.
     */
    protected function dateDiffDays(string $date1, string $date2): string
    {
        if ($this->isUsingSqlite()) {
            return "CAST((julianday({$date1}) - julianday({$date2})) AS INTEGER)";
        }

        return "DATEDIFF({$date1}, {$date2})";
    }

    /**
     * Add days to date - cross-database compatible.
     */
    protected function dateAddDays(string $date, int $days): string
    {
        if ($this->isUsingSqlite()) {
            return "date({$date}, '+{$days} days')";
        }

        return "DATE_ADD({$date}, INTERVAL {$days} DAY)";
    }

    /**
     * Convert PHP date format to SQLite strftime format.
     */
    private function convertToSqliteFormat(string $phpFormat): string
    {
        $replacements = [
            'Y' => '%Y', // 4-digit year
            'y' => '%y', // 2-digit year
            'm' => '%m', // Month (01-12)
            'n' => '%m', // Month (1-12) - SQLite doesn't have this
            'd' => '%d', // Day (01-31)
            'j' => '%d', // Day (1-31) - SQLite doesn't have this
            'H' => '%H', // Hour (00-23)
            'i' => '%M', // Minutes (00-59)
            's' => '%S', // Seconds (00-59)
            'W' => '%W', // Week number
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $phpFormat);
    }

    /**
     * Convert PHP date format to MySQL DATE_FORMAT format.
     */
    private function convertToMysqlFormat(string $phpFormat): string
    {
        $replacements = [
            'Y' => '%Y', // 4-digit year
            'y' => '%y', // 2-digit year
            'm' => '%m', // Month (01-12)
            'n' => '%c', // Month (1-12)
            'd' => '%d', // Day (01-31)
            'j' => '%e', // Day (1-31)
            'H' => '%H', // Hour (00-23)
            'i' => '%i', // Minutes (00-59)
            's' => '%s', // Seconds (00-59)
            'W' => '%v', // Week number
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $phpFormat);
    }
}
