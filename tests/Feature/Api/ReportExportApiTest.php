<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Payment;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\WastedMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportExportApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $designer;

    private User $production;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = Branch::create([
            'name' => 'Branch One',
            'code' => 'B1',
        ]);
        $this->branchTwo = Branch::create([
            'name' => 'Branch Two',
            'code' => 'B2',
        ]);

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->designer = $this->user('designer', $this->branchOne);
        $this->production = $this->user('production', $this->branchOne);

        $customer = Customer::create([
            'name' => 'Budi Pelanggan',
            'phone' => '081234567890',
            'type' => 'Reguler',
        ]);

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
        ]);

        $material = Material::create([
            'name' => 'Flexy 280gr',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'unit' => 'm2',
            'cost' => 10000,
            'price' => 20000,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-B1-20261127-0001',
            'branch_id' => $this->branchOne->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'designer_id' => $this->designer->id,
            'status' => 2,
            'discount' => 0,
            'dp_amount' => 100000,
            'accounts_receivable' => 0,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toDateString(),
            'pickup_status' => 0,
            'source' => 0,
        ]);

        Payment::create([
            'branch_id' => $this->branchOne->id,
            'invoice_id' => $invoice->id,
            'user_id' => $this->cashier->id,
            'amount' => 100000,
            'payment_method' => 'Cash',
            'type' => 'settlement',
            'cashier_name' => $this->cashier->name,
        ]);

        Expense::create([
            'branch_id' => $this->branchOne->id,
            'incurred_at' => now()->toDateString(),
            'amount' => 15000,
            'description' => 'Tinta printer',
            'cashier_name' => $this->cashier->name,
            'type' => 'laba',
            'category' => 'Supplies',
        ]);

        WastedMaterial::create([
            'branch_id' => $this->branchOne->id,
            'logged_at' => now(),
            'material_id' => $material->id,
            'designer_id' => $this->designer->id,
            'operator_id' => $this->production->id,
            'quantity' => 1.5,
            'notes' => 'Salah potong',
        ]);
    }

    public function test_report_export_enforces_authentication_and_role_permissions(): void
    {
        $today = now()->toDateString();
        $url = "/api/v1/reports/gross-revenue/export?format=xlsx&start_date={$today}&end_date={$today}";

        $this->getJson($url)->assertUnauthorized();

        $branchlessManager = $this->user('manager', null);

        foreach ([$this->cashier, $this->designer, $this->production, $branchlessManager] as $actor) {
            $this->actingAs($actor)
                ->getJson($url)
                ->assertForbidden();
        }
    }

    public function test_report_export_enforces_branch_scope_for_manager(): void
    {
        $today = now()->toDateString();

        $this->actingAs($this->manager)
            ->getJson("/api/v1/reports/net-profit/export?format=pdf&branch_id=all&start_date={$today}&end_date={$today}")
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->getJson("/api/v1/reports/net-profit/export?format=xlsx&branch_id={$this->branchTwo->id}&start_date={$today}&end_date={$today}")
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->getJson("/api/v1/reports/net-profit/export?format=xlsx&branch_id={$this->branchOne->id}&start_date={$today}&end_date={$today}")
            ->assertOk();
    }

    public function test_report_export_validates_type_format_period_and_date_range(): void
    {
        $today = now()->toDateString();

        $this->actingAs($this->owner)
            ->getJson("/api/v1/reports/unknown/export?format=xlsx&start_date={$today}&end_date={$today}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);

        $this->actingAs($this->owner)
            ->getJson("/api/v1/reports/gross-revenue/export?start_date={$today}&end_date={$today}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['format']);

        $this->actingAs($this->owner)
            ->getJson("/api/v1/reports/gross-revenue/export?format=csv&start_date={$today}&end_date={$today}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['format']);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/gross-revenue/export?format=pdf&period=yearly')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period']);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/expenses/export?format=xlsx&start_date=2026-11-10&end_date=2026-11-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_owner_and_manager_can_export_all_reports_as_xlsx_and_pdf_with_audit_trail(): void
    {
        $today = now()->toDateString();
        $types = ['gross-revenue', 'base-cost', 'net-profit', 'expenses'];

        foreach ($types as $type) {
            $xlsxResponse = $this->actingAs($this->owner)
                ->get("/api/v1/reports/{$type}/export?format=xlsx&period=daily&branch_id=all&start_date={$today}&end_date={$today}")
                ->assertOk();

            $this->assertSame(
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                $xlsxResponse->headers->get('Content-Type')
            );
            $this->assertSame(
                'attachment; filename="laporan-'.$type.'-all-'.$today.'_'.$today.'.xlsx"',
                $xlsxResponse->headers->get('Content-Disposition')
            );
            $this->assertStringStartsWith('PK', $xlsxResponse->streamedContent());

            $pdfResponse = $this->actingAs($this->owner)
                ->get("/api/v1/reports/{$type}/export?format=pdf&period=daily&branch_id=all&start_date={$today}&end_date={$today}")
                ->assertOk();

            $this->assertSame('application/pdf', $pdfResponse->headers->get('Content-Type'));
            $this->assertSame(
                'attachment; filename="laporan-'.$type.'-all-'.$today.'_'.$today.'.pdf"',
                $pdfResponse->headers->get('Content-Disposition')
            );
            $this->assertStringStartsWith('%PDF-', (string) $pdfResponse->getContent());
        }

        $ownerAudit = ActivityLog::query()
            ->where('action', 'EXPORT')
            ->where('user_id', $this->owner->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($ownerAudit);
        $this->assertSame('owner', $ownerAudit->role);
        $this->assertNull($ownerAudit->branch_id);
        $this->assertSame('all', $ownerAudit->payload['branch_id'] ?? null);

        $managerPdf = $this->actingAs($this->manager)
            ->get("/api/v1/reports/expenses/export?format=pdf&period=monthly&start_date={$today}&end_date={$today}")
            ->assertOk();

        $this->assertSame(
            'attachment; filename="laporan-expenses-b1-'.$today.'_'.$today.'.pdf"',
            $managerPdf->headers->get('Content-Disposition')
        );
        $this->assertStringStartsWith('%PDF-', (string) $managerPdf->getContent());

        $managerAudit = ActivityLog::query()
            ->where('action', 'EXPORT')
            ->where('user_id', $this->manager->id)
            ->first();

        $this->assertNotNull($managerAudit);
        $this->assertSame('manager', $managerAudit->role);
        $this->assertSame($this->branchOne->id, $managerAudit->branch_id);
        $this->assertSame('expenses', $managerAudit->payload['report_type'] ?? null);
        $this->assertSame('pdf', $managerAudit->payload['format'] ?? null);
        $this->assertSame($this->branchOne->id, $managerAudit->payload['branch_id'] ?? null);
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'name' => Str::title($role).' Tester',
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }
}
