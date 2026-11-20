# User Identification and Access Rights Matrix (Target Specification)

- **Source:** Capstone proposal role table, FR1–FR26, and the agreed scope decisions.
- **Role mapping:** Pemilik = `owner`, Branch Manager = `manager`, Kasir = `cashier`, Designer = `designer`, Produksi = `production`.

## 1. Target Role Summary

| Role | Data Akun | Data Transaksi | Data Stok | Status & Antrean Produksi | Laporan Keuangan |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Pemilik | CRUD | R | CRUD | R | R |
| Branch Manager | CRUD (branch staff) | R | CRUD (branch) | R | R (limited) |
| Kasir | - | CRU | - | R | - |
| Designer | - | CRU | - | R | - |
| Produksi | - | R | RU | RU | - |

Notes:

- **Kasir `U` on transactions** covers pickup status only (FR21). Order changes are Designer-only. Kasir cannot edit payment amounts after posting.
- **Designer `U` on transactions** covers order changes before production (FR9) and design-status updates (FR10).
- **Manager `Data Stok`** is CRUD for the manager's branch. Global material deletion remains owner-only.
- **Owner `Data Stok`** is CRUD through material management; the reason-required adjustment workflow (FR18) is manager-scoped unless extended to the owner.
- **Manager `Laporan Keuangan`** is limited to branch dashboards, designer revenue, and expenses; it is not the owner's full report set.
- **Kasir `Data Stok`:** no stock access. Purchases and stock visibility are handled by Owner and Branch Manager; Production can view stock.
- **Production `Data Stok`** is `RU`: view stock and record usage/waste. Production cannot edit material master data.

## 2. Capability Matrix

| Module | Capability | Owner | Manager | Cashier | Designer | Production |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| Auth | Login, logout, own password | Yes | Yes | Yes | Yes | Yes |
| Users | Create user | Yes | Branch staff | No | No | No |
| Users | Update user, role, deactivate | Yes | Branch staff | No | No | No |
| Users | View own profile | Yes | Yes | Yes | Yes | Yes |
| Users | Update own name/email | Yes | Yes | No | No | No |
| Access | Role and branch enforcement | Yes | Yes | Yes | Yes | Yes |
| Products | Create/edit/delete product | Yes | Yes | No | No | No |
| Products | Manage categories | Yes | Yes | No | No | No |
| Products | Manage pricing matrix | Yes | Yes | No | No | No |
| Products | Search/filter/paginate products | Yes | Yes | Yes | Yes | No |
| Machines | Manage printer machines | Yes | Branch | No | No | No |
| Branches | Manage branches | Yes | No | No | No | No |
| Orders | Create order | No | No | No | Yes | No |
| Orders | Update order before production | No | No | No | Yes | No |
| Orders | Update design status | No | No | No | Yes | No |
| Orders | View order or transaction list | All | Branch | Branch | Branch | Branch |
| Orders | View production queue | All | Branch | Branch | Branch | Branch |
| Orders | Update production status | No | No | No | No | Yes |
| Orders | Mark picked up | No | No | Yes | No | No |
| Payments | Register DP and settlement | No | No | Yes | No | No |
| Payments | Multiple DP until paid | No | No | Yes | No | No |
| Payments | Print receipt | No | No | Yes | No | No |
| Expenses | Record operational expense | No | No | Yes | No | No |
| Inventory | Record raw-material purchase | Yes | Yes | No | No | No |
| Inventory | Record material usage on order | No | No | No | No | Yes |
| Inventory | View stock per branch | All | Branch | No | No | Branch |
| Inventory | Low-stock warning | All | Branch | No | No | Branch |
| Inventory | Adjust stock with reason | No | Branch | No | No | No |
| Inventory | Manage material master | Yes | Branch | No | No | No |
| Customers | Create/search customers | No | No | Search | Yes | No |
| Printing | Print ticket and receipt | Yes | Yes | Yes | Yes | Yes |
| Reports | Dashboard summary | All | Branch | No | No | No |
| Reports | Periodic financial reports | All | Branch (limited) | No | No | No |
| Reports | Export Excel/PDF | Yes | Branch | No | No | No |
| System | View audit log | Yes | No | No | No | No |
| System | Write audit log | System | System | System | System | System |
| System | Manual backup | Yes | No | No | No | No |
| System | Scheduled backup | System | System | System | System | System |

## 3. Branch Scoping Rules

| Entity | Rule |
| :--- | :--- |
| Invoice, order, payment | Branch-bound users only see their branch; owner sees all |
| Material and stock | Manager operates on own branch; owner all branches |
| Machine | Manager own branch; owner global |
| Pricing and attributes | Manager own branch; owner all |
| User | Manager own-branch staff; owner all |
| Expense, waste, stock ledger | Branch-scoped |
| Ticket and receipt | Non-owner blocked across branches; owner allowed |
| Reports | Owner all or selected; manager own branch |

## 4. Profile Rights

| Action | Owner | Manager | Cashier | Designer | Production |
| :--- | :--- | :--- | :--- | :--- | :--- |
| View own profile | Yes | Yes | Yes | Yes | Yes |
| Update name and email | Yes | Yes | No | No | No |
| Update password | Yes | Yes | Yes | Yes | Yes |
| Delete own account | Yes | Yes | No | No | No |

## 5. Target Enforcement Requirements

1. Every route, Livewire action, and service method SHALL derive the actor, role, and branch from the authenticated user server-side.
2. Client-writable scope fields SHALL be removed, locked, or validated against the authenticated user.
3. A branch-bound user SHALL receive HTTP 403 when the record belongs to another branch.
4. Production views and exports SHALL omit price, cost, and profit fields.
5. Manager user management SHALL be limited to cashier, designer, and production in the manager's branch.
6. Manager stock management SHALL be limited to the manager's branch; global material deletion SHALL remain owner-only.
7. Owner-only actions: branch management, manual backup, audit view, owner-account management.
8. Cashier payment editing SHALL be limited to creating DP/settlement and updating pickup status; posted payment amounts are immutable, and transaction update is limited to pickup.
9. Designer order editing SHALL be limited to invoices whose orders are all still `status = 0`.
10. Cashier SHALL NOT access stock screens or stock actions. Stock purchases are Owner/Branch Manager; stock visibility is Owner/Branch Manager/Production.
11. Access rules SHALL be covered by feature tests per role and per branch.
