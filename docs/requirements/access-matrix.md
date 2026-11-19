# User Identification and Access Rights Matrix (Target Specification)

- **Source:** Capstone proposal role table, FR1–FR26, and the agreed scope decisions.
- **Status legend:** Implemented / Partial / Target.
- **Role mapping:** Pemilik = `owner`, Branch Manager = `manager`, Kasir = `cashier`, Designer = `designer`, Produksi = `production`.

## 1. Target Role Summary

| Role | Data Akun | Data Transaksi | Data Stok | Status & Antrean Produksi | Laporan Keuangan |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Pemilik | CRUD | R | CRUD | R | R |
| Branch Manager | CRUD (branch staff) | R | CRUD (branch) | R | R (limited) |
| Kasir | - | CRU | CR | R | - |
| Designer | - | CRU | - | R | - |
| Produksi | - | R | RU | RU | - |

Notes:

- **Kasir `U` on transactions** covers order changes before production (FR9) and pickup status (FR21). It does not allow editing payment amounts after posting.
- **Designer `U` on transactions** covers order changes before production (FR9) and design-status updates (FR10).
- **Manager `Data Stok`** is CRUD for the manager's branch. Global material deletion remains owner-only.
- **Owner `Data Stok`** is CRUD through material management; the reason-required adjustment workflow (FR18) is manager-scoped unless extended to the owner.
- **Manager `Laporan Keuangan`** is limited to branch dashboards, designer revenue, and expenses; it is not the owner's full report set.
- **Kasir `Data Stok`** is `CR`: record purchases (create) and view stock. Stock adjustment is manager-only.
- **Production `Data Stok`** is `RU`: view stock and record usage/waste. Production cannot edit material master data.

## 2. Capability Matrix

| Module | Capability | Owner | Manager | Cashier | Designer | Production | Current |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| Auth | Login, logout, own password | Yes | Yes | Yes | Yes | Yes | Implemented |
| Users | Create user | Yes | Branch staff | No | No | No | Partial; mode caveat |
| Users | Update user, role, deactivate | Yes | Branch staff | No | No | No | Partial; mode caveat |
| Users | View own profile | Yes | Yes | Yes | Yes | Yes | Implemented |
| Users | Update own name/email | Yes | Yes | No | No | No | Implemented |
| Access | Role and branch enforcement | Yes | Yes | Yes | Yes | Yes | Partial |
| Products | Create/edit/delete product | Yes | Yes | No | No | No | Target |
| Products | Manage categories | Yes | Yes | No | No | No | Implemented |
| Products | Manage pricing matrix | Yes | Yes | No | No | No | Partial; owner has no tab |
| Products | Search/filter/paginate products | Yes | Yes | Yes | Yes | No | Target |
| Machines | Manage printer machines | Yes | Branch | No | No | No | Implemented |
| Branches | Manage branches | Yes | No | No | No | No | Implemented |
| Orders | Create order | No | No | No | Yes | No | Implemented |
| Orders | Update order before production | No | No | Yes | Yes | No | Target |
| Orders | Update design status | No | No | No | Yes | No | Target |
| Orders | View order or transaction list | All | Branch | Branch | Branch | Branch | Partial |
| Orders | View production queue | All | Branch | Branch | Branch | Branch | Partial |
| Orders | Update production status | No | No | No | No | Yes | Implemented |
| Orders | Mark picked up | No | No | Yes | No | No | Partial |
| Payments | Register DP and settlement | No | No | Yes | No | No | Partial; DP cap is `grand_total`, not remaining |
| Payments | Multiple DP until paid | No | No | Yes | No | No | Partial; overpayment possible |
| Payments | Print receipt | No | No | Yes | No | No | Implemented |
| Expenses | Record operational expense | No | No | Yes | No | No | Implemented |
| Inventory | Record raw-material purchase | No | No | Yes | No | No | Target |
| Inventory | Record material usage on order | No | No | No | No | Yes | Partial |
| Inventory | View stock per branch | All | Branch | Branch | No | Branch | Partial |
| Inventory | Low-stock warning | All | Branch | Branch | No | Branch | Partial |
| Inventory | Adjust stock with reason | No | Branch | No | No | No | Target |
| Inventory | Manage material master | Yes | Branch | No | No | No | Partial |
| Customers | Create/search customers | No | No | Search | Yes | No | Implemented |
| Printing | Print ticket and receipt | Yes | Yes | Yes | Yes | Yes | Implemented |
| Reports | Dashboard summary | All | Branch | No | No | No | Partial |
| Reports | Periodic financial reports | All | Branch (limited) | No | No | No | Partial |
| Reports | Export Excel/PDF | Yes | Branch | No | No | No | Target |
| System | View audit log | Yes | No | No | No | No | Implemented |
| System | Write audit log | System | System | System | System | System | Partial |
| System | Manual backup | Yes | No | No | No | No | Target |
| System | Scheduled backup | System | System | System | System | System | Target |

## 3. Branch Scoping Rules

| Entity | Rule | Current enforcement |
| :--- | :--- | :--- |
| Invoice, order, payment | Branch-bound users only see their branch; owner sees all | Partial; cashier invoice selection has no assertion |
| Material and stock | Manager operates on own branch; owner all branches | Partial; material `branchId` is client-writable |
| Machine | Manager own branch; owner global | Implemented |
| Pricing and attributes | Manager own branch; owner all | Partial; owner has no pricing UI |
| User | Manager own-branch staff; owner all | Partial; `mode` is client-writable |
| Expense, waste, stock ledger | Branch-scoped | Implemented |
| Ticket and receipt | Non-owner blocked across branches; owner allowed | Implemented |
| Reports | Owner all or selected; manager own branch | Partial; manager report set is limited |

## 4. Profile Rights

| Action | Owner | Manager | Cashier | Designer | Production |
| :--- | :--- | :--- | :--- | :--- | :--- |
| View own profile | Yes | Yes | Yes | Yes | Yes |
| Update name and email | Yes | Yes | No | No | No |
| Update password | Yes | Yes | Yes | Yes | Yes |
| Delete own account | Yes | Yes | No | No | No |

## 5. Target Enforcement Requirements

1. Every route, Livewire action, and service method SHALL derive the actor, role, and branch from the authenticated user server-side.
2. Client-writable scope fields SHALL be removed, locked, or validated against the authenticated user. `UserManagement::$mode` and `MaterialManagement::$branchId` violate this today.
3. A branch-bound user SHALL receive HTTP 403 when the record belongs to another branch.
4. Production views and exports SHALL omit price, cost, and profit fields.
5. Manager user management SHALL be limited to cashier, designer, and production in the manager's branch.
6. Manager stock management SHALL be limited to the manager's branch; global material deletion SHALL remain owner-only.
7. Owner-only actions: branch management, manual backup, audit view, owner-account management.
8. Cashier payment editing SHALL be limited to creating DP/settlement and updating pickup status; posted payment amounts are immutable.
9. Designer order editing SHALL be limited to invoices whose orders are all still `status = 0`.
10. Access rules SHALL be covered by feature tests per role and per branch.

## 6. Current Access Gaps

1. `UserManagement::$mode` is client-writable and has no mount-time role guard.
2. `MaterialManagement::$branchId` is client-writable; a manager can delete a global material used by other branches.
3. Cashier `selectInvoice()` and `processCheckout()` lack an explicit branch assertion.
4. Dashboard components do not assert role in `mount()`; role dispatch happens in `dashboard.blade.php`.
5. Ticket and receipt routes check branch but not role.
6. A null `branch_id` skips branch checks; this is intended for owners but is not role-verified.
7. No per-record authorization restricts a designer to their own orders.
8. Product CRUD, product search/pagination, order updates, design status, stock adjustment, export, and backup do not exist yet.
9. Audit coverage excludes payments, order updates, and stock adjustments.
10. `RoleMiddleware` is registered but unused by any route.
