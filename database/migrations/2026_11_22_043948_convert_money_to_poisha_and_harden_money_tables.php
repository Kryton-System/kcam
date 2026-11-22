<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money columns: table => columns. Existing values are multiplied by 100 (taka to poisha).
     * fee_discounts.value is poisha for fixed discounts and basis points for percentages
     * (10% becomes 1000), which is also value * 100.
     *
     * @var array<string, list<string>>
     */
    private array $moneyColumns = [
        'invoices' => ['total', 'paid', 'due'],
        'invoice_items' => ['amount'],
        'payments' => ['amount'],
        'fee_structures' => ['amount'],
        'fee_discounts' => ['value'],
        'staff' => ['salary_basic'],
        'sms_logs' => ['cost'],
    ];

    public function up(): void
    {
        $this->convertMoneyToPoisha();
        $this->addInvoiceItemSchool();
        $this->restrictMoneyForeignKeys();
        $this->addUniqueKeys();
        $this->createReceiptSequences();
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_sequences');

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['school_id', 'client_uuid']);
            $table->dropColumn('client_uuid');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['school_id', 'student_id', 'academic_year_id', 'month']);
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });

        foreach ($this->moneyColumns as $tableName => $columns) {
            foreach ($columns as $column) {
                DB::table($tableName)->update([$column => DB::raw("{$column} / 100")]);
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns): void {
                foreach ($columns as $column) {
                    $table->decimal($column, $tableName === 'sms_logs' ? 8 : 12, $tableName === 'sms_logs' ? 4 : 2)->default(0)->change();
                }
            });
        }
    }

    private function convertMoneyToPoisha(): void
    {
        foreach ($this->moneyColumns as $tableName => $columns) {
            foreach ($columns as $column) {
                DB::table($tableName)->update([$column => DB::raw("ROUND({$column} * 100)")]);
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->unsignedBigInteger($column)->default(0)->change();
                }
            });
        }
    }

    private function addInvoiceItemSchool(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('school_id')->nullable()->after('id');
        });

        DB::table('invoice_items')->update([
            'school_id' => DB::raw('(select school_id from invoices where invoices.id = invoice_items.invoice_id)'),
        ]);

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('school_id')->nullable(false)->change();
            $table->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
            $table->index(['school_id', 'invoice_id']);
        });
    }

    /**
     * Money rows must never disappear because a parent row was deleted.
     */
    private function restrictMoneyForeignKeys(): void
    {
        $foreignKeys = [
            'invoices' => ['school_id' => 'schools', 'student_id' => 'students', 'academic_year_id' => 'academic_years'],
            'invoice_items' => ['invoice_id' => 'invoices', 'fee_head_id' => 'fee_heads'],
            'payments' => ['school_id' => 'schools', 'invoice_id' => 'invoices'],
            'receipts' => ['school_id' => 'schools', 'payment_id' => 'payments'],
        ];

        foreach ($foreignKeys as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                foreach (array_keys($columns) as $column) {
                    $table->dropForeign([$column]);
                }
            });

            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column => $referenced) {
                    $table->foreign($column)->references('id')->on($referenced)->restrictOnDelete();
                }
            });
        }
    }

    private function addUniqueKeys(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique(['school_id', 'student_id', 'academic_year_id', 'month']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->uuid('client_uuid')->nullable()->after('gateway_txn_id');
            $table->unique(['school_id', 'client_uuid']);
        });
    }

    /**
     * One row per school and year, locked while a receipt number is taken, so serials
     * are gapless and never derived from counting rows.
     */
    private function createReceiptSequences(): void
    {
        Schema::create('receipt_sequences', function (Blueprint $table): void {
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('next_no')->default(1);

            $table->primary(['school_id', 'year']);
        });

        $counts = [];

        foreach (DB::table('receipts')->get(['school_id', 'created_at']) as $receipt) {
            $key = $receipt->school_id.'|'.substr((string) $receipt->created_at, 0, 4);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        foreach ($counts as $key => $count) {
            [$schoolId, $year] = explode('|', $key);

            DB::table('receipt_sequences')->insert([
                'school_id' => (int) $schoolId,
                'year' => (int) $year,
                'next_no' => $count + 1,
            ]);
        }
    }
};
