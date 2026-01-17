<?php

declare(strict_types=1);

use App\Concerns\DatabaseCompatible;

// Create a test class that uses the trait
class DatabaseCompatibleTestClass
{
    use DatabaseCompatible;

    // Expose protected methods for testing
    public function testGetDatabaseDriver(): string
    {
        return $this->getDatabaseDriver();
    }

    public function testIsUsingSqlite(): bool
    {
        return $this->isUsingSqlite();
    }

    public function testIsUsingMysql(): bool
    {
        return $this->isUsingMysql();
    }

    public function testYearFromDate(string $column): string
    {
        return $this->yearFromDate($column);
    }

    public function testMonthFromDate(string $column): string
    {
        return $this->monthFromDate($column);
    }

    public function testDayFromDate(string $column): string
    {
        return $this->dayFromDate($column);
    }

    public function testCurrentTimestamp(): string
    {
        return $this->currentTimestamp();
    }

    public function testCurrentDate(): string
    {
        return $this->currentDate();
    }

    public function testCoalesce(array $columns): string
    {
        return $this->coalesce($columns);
    }

    public function testConcat(array $values): string
    {
        return $this->concat($values);
    }

    public function testGroupConcat(string $column, string $separator = ','): string
    {
        return $this->groupConcat($column, $separator);
    }

    public function testDateDiffDays(string $date1, string $date2): string
    {
        return $this->dateDiffDays($date1, $date2);
    }

    public function testDateAddDays(string $date, int $days): string
    {
        return $this->dateAddDays($date, $days);
    }

    public function testDateFormat(string $column, string $format): string
    {
        return $this->dateFormat($column, $format);
    }
}

describe('DatabaseCompatible Trait', function (): void {
    beforeEach(function (): void {
        $this->helper = new DatabaseCompatibleTestClass;
    });

    it('detects SQLite driver', function (): void {
        // Since tests run on SQLite by default
        expect($this->helper->testGetDatabaseDriver())->toBe('sqlite');
    });

    it('returns true for isUsingSqlite when using SQLite', function (): void {
        expect($this->helper->testIsUsingSqlite())->toBeTrue();
    });

    it('returns false for isUsingMysql when using SQLite', function (): void {
        expect($this->helper->testIsUsingMysql())->toBeFalse();
    });

    describe('year extraction', function (): void {
        it('generates SQLite strftime for year', function (): void {
            $result = $this->helper->testYearFromDate('created_at');

            expect($result)->toBe("strftime('%Y', created_at)");
        });
    });

    describe('month extraction', function (): void {
        it('generates SQLite strftime for month', function (): void {
            $result = $this->helper->testMonthFromDate('created_at');

            expect($result)->toBe("strftime('%m', created_at)");
        });
    });

    describe('day extraction', function (): void {
        it('generates SQLite strftime for day', function (): void {
            $result = $this->helper->testDayFromDate('created_at');

            expect($result)->toBe("strftime('%d', created_at)");
        });
    });

    describe('current timestamp', function (): void {
        it('generates SQLite datetime function', function (): void {
            $result = $this->helper->testCurrentTimestamp();

            expect($result)->toBe("datetime('now')");
        });
    });

    describe('current date', function (): void {
        it('generates SQLite date function', function (): void {
            $result = $this->helper->testCurrentDate();

            expect($result)->toBe("date('now')");
        });
    });

    describe('coalesce', function (): void {
        it('generates COALESCE with single column', function (): void {
            $result = $this->helper->testCoalesce(['name']);

            expect($result)->toBe('COALESCE(name)');
        });

        it('generates COALESCE with multiple columns', function (): void {
            $result = $this->helper->testCoalesce(['name', 'username', "'Unknown'"]);

            expect($result)->toBe("COALESCE(name, username, 'Unknown')");
        });
    });

    describe('concat', function (): void {
        it('generates SQLite concatenation with ||', function (): void {
            $result = $this->helper->testConcat(['first_name', "' '", 'last_name']);

            expect($result)->toBe("first_name || ' ' || last_name");
        });
    });

    describe('group concat', function (): void {
        it('generates SQLite GROUP_CONCAT with default separator', function (): void {
            $result = $this->helper->testGroupConcat('tag');

            expect($result)->toBe("GROUP_CONCAT(tag, ',')");
        });

        it('generates SQLite GROUP_CONCAT with custom separator', function (): void {
            $result = $this->helper->testGroupConcat('tag', ' | ');

            expect($result)->toBe("GROUP_CONCAT(tag, ' | ')");
        });
    });

    describe('date diff days', function (): void {
        it('generates SQLite julianday difference', function (): void {
            $result = $this->helper->testDateDiffDays('end_date', 'start_date');

            expect($result)->toBe('CAST((julianday(end_date) - julianday(start_date)) AS INTEGER)');
        });
    });

    describe('date add days', function (): void {
        it('generates SQLite date addition', function (): void {
            $result = $this->helper->testDateAddDays('created_at', 7);

            expect($result)->toBe("date(created_at, '+7 days')");
        });

        it('handles negative days', function (): void {
            $result = $this->helper->testDateAddDays('created_at', -3);

            expect($result)->toBe("date(created_at, '+-3 days')");
        });
    });

    describe('date format', function (): void {
        it('converts Y-m-d format for SQLite', function (): void {
            $result = $this->helper->testDateFormat('created_at', 'Y-m-d');

            expect($result)->toBe("strftime('%Y-%m-%d', created_at)");
        });

        it('converts Y-m format for SQLite', function (): void {
            $result = $this->helper->testDateFormat('created_at', 'Y-m');

            expect($result)->toBe("strftime('%Y-%m', created_at)");
        });
    });
});
