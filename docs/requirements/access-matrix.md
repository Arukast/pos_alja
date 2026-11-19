# User Identification and Access Rights Matrix

- **System:** Print Shop POS
- **Roles:** `owner`, `manager`, `cashier`, `designer`, `production`
- **Scope:** Access to dashboards, navigation tabs, administrative features, and operational actions.
- **Source of truth:** role dispatch in `resources/views/dashboard.blade.php`, navigation in `resources/views/livewire/layout/partials/navigation/_role_links.blade.php`, component authorization checks, and route closures.

This matrix describes intended role behavior and the current UI routing. It is not a guarantee of server-side enforcement: several components rely on dashboard dispatch rather than a mount-time role guard. See Known Access Gaps.

Legend:

- **All** — every record, including all branches.
- **Own branch** — records whose `branch_id` equals the authenticated user's `branch_id`.
- **Own** — the authenticated user's own profile or records they created.
- **Read** — visible but not editable.
- **No** — not exposed and rejected with HTTP 403 where an endpoint exists.

---

## 1. Identity Model

| Attribute | Rule |
| :--- | :--- |
| Login identifier | `username` plus password; no email login |
| Role storage | `users.role` string: `owner`, `manager`, `cashier`, `designer`, `production` |
| Branch binding | `users.branch_id`; nullable for owners |
| Account creation | Owner or manager only; no self-registration |
| Session | Laravel database session; invalidated on logout |
| Email verification | `users.email_verified_at` exists; `MustVerifyEmail` is not enabled on the model |
| Soft deletes | Users are soft deleted and can be restored by an authorized administrator |

---

## 2. Navigation and Dashboard Tabs

Every role lands on `/dashboard`, which renders exactly one dashboard component based on `users.role`.

| Tab / screen | Owner | Manager | Cashier | Designer | Production |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Dashboard overview | Yes | Yes | Yes | Yes | Yes |
| Gross Revenue Detail (`revenue`) | Yes | No | No | No | No |
| Base Cost Detail (`cost`) | Yes | No | No | No | No |
| Net Profit Detail (`profit`) | Yes | No | No | No | No |
| Expenses Detail (`expenses`) | Yes | No | No | No | No |
| Audit Trail (`audit`) | Yes | No | No | No | No |
| Financial summary (`finance`, default) | Yes | No | No | No | No |
| Designer Leaderboard (`leaderboard`) | No | Yes | No | No | No |
| Materials & Stock (`materials`) | Yes | Yes | No | No | No |
| Stock Ledgers (`ledgers`) | No | Yes | No | No | No |
| Display & Finishing (`finishing`) | No | Yes | No | No | No |
| Product Categories (`categories`) | Yes | Yes | No | No | No |
| Printer Machines (`machines`) | Yes | Yes | No | No | No |
| Pricing Matrices (`pricing`) | No | Yes | No | No | No |
| User Management (`users`) | Yes | Yes (branch staff) | No | No | No |
| Branch Management (`branches`) | Yes | No | No | No | No |
| Payment Queue (`payments`) | No | No | Yes | No | No |
| Pickup Queue (`pickup`) | No | No | Yes | No | No |
| Cashier Expenses (`expenses`) | No | No | Yes | No | No |
| New Order (`new_order`) | No | No | No | Yes | No |
| Order List (`order_list`) | No | No | No | Yes | No |
| Print Queue (`print_queue`) | No | No | No | No | Yes |
| Waste Logging (`waste`) | No | No | No | No | Partial (tab render bug; service works) |

`owner` also has a `materials` tab that opens the global material management component, and a `users` tab that opens user management in owner mode.

---

## 3. Administrative Capabilities

| Capability | Owner | Manager | Cashier | Designer | Production |
| :--- | :--- | :--- | :--- | :--- | :--- |
| View branches | All | No screen (scope only) | No | No | No |
| Create / edit / toggle / delete branches | Yes | No | No | No | No |
| View users | All | Own branch | Own profile | Own profile | Own profile |
| Create users | Yes | Own branch staff only (manager mode) | No | No | No |
| Edit users | Yes | Own branch staff only (manager mode) | Own profile (password only) | Own profile (password only) | Own profile (password only) |
| Delete / restore users | Yes | Own branch staff only (manager mode) | No | No | No |
| Create / edit owner accounts | Primary owner only | No | No | No | No |
| Create / edit manager accounts | Yes | No | No | No | No |
| Product categories | Yes | Yes | No | No | No |
| Attribute types | Yes | Yes | No | No | No |
| Display / finishing / cut attributes | No dedicated screen | Own branch | No | No | No |
| Materials and branch stock | All branches | Own branch by default; `branchId` is client-selectable (no server-side guard) | No | No | No |
| Stock ledgers | No screen | Own branch | No | No | No |
| Printer machines | All branches | Own branch | No | No | No |
| Pricing matrices | No | Own branch | No | No | No |
| Clone pricing tiers | No | Yes | No | No | No |

Authorization boundaries:

- Branch management requires `role === 'owner'` in `mount()`.
- User management requires owner mode or manager mode; managers cannot touch `owner` or `manager` records, and cannot move a user out of their branch.
- Category, machine, and material management require `manager` or `owner` at the action level. Material management has no mount-time role guard, and its target branch is client-selectable.
- Machine managers are restricted to `auth()->user()->branch_id`, and the machine component enforces it. Material management does not enforce that restriction server-side; its target branch is client-selectable.
- A secondary owner (not the primary owner) cannot create, edit, delete, or restore owner accounts.
- `UserManagement` has no mount-time role guard, and its `mode` property is client-writable; the manager restrictions apply only while `mode` remains `manager`.

---

## 4. Operational Capabilities

| Capability | Owner | Manager | Cashier | Designer | Production |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Create customers | No | No | No | Yes | No |
| Search / select customers | No | No | No | Yes | No |
| Calculate quotes | No | No | No | Yes | No |
| Build a cart and submit an invoice | No | No | No | Yes | No |
| View order list | No | No | No | Own branch | No |
| Update a submitted order to add items | No | No | No | Planned | No |
| Start production | No | No | No | No | Own branch |
| Complete production | No | No | No | No | Own branch |
| Log material waste | No | No | No | No | Own branch |
| View print queue | No | No | No | No | Own branch |
| Register down payment | No | No | Own branch | No | No |
| Settle invoice | No | No | Own branch | No | No |
| Log drawer expense | No | No | Own branch | No | No |
| View payment queue | No | No | Own branch | No | No |
| Mark invoice picked up | No | No | Own branch | No | No |
| View pickup queue | No | No | Own branch | No | No |
| Print job ticket | Yes | Yes | Yes | Yes | Yes |
| Print receipt | Yes | Yes | Yes | Yes | Yes |

Ticket and receipt routes are available to any authenticated user. A non-owner whose `branch_id` is set is rejected with HTTP 403 when the invoice belongs to another branch. An owner bypasses that check.

Planned capstone scope: Designer `CRU` on Data Transaksi includes updating a submitted order to add items (FR-ORD-08). The current code supports create and read only.

---

## 5. Financial Visibility

| Metric / view | Owner | Manager | Cashier | Designer | Production |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Gross revenue (payments) | All branches / selected | No | No gross revenue view; today's DP and settlement totals only | No | No |
| Base cost (HPP) | All branches / selected | No | No | No | No |
| Net profit detail | All branches / selected | No | No | No | No |
| Expenses breakdown | All branches / selected | Recent own branch (dashboard) | Own branch (logged and recent) | No | No |
| Waste cost | Consolidated in cost and profit detail | Own branch leaderboard context | No | No | Own branch quantity (logged) |
| Audit trail | All | No | No | No | No |
| Designer leaderboard | No | Own branch, current month | No | No | No |

---

## 6. Profile Rights

| Action | Owner | Manager | Cashier | Designer | Production |
| :--- | :--- | :--- | :--- | :--- | :--- |
| View own profile | Yes | Yes | Yes | Yes | Yes |
| Update name and email | Yes | Yes | No | No | No |
| Update password | Yes | Yes | Yes | Yes | Yes |
| Delete own account | Yes | Yes | No | No | No |

The restriction is implemented in `UpdateProfileInformationForm::updateProfileInformation()` and `DeleteUserForm::deleteUser()`. Password changes use the current-password check for all roles.

---

## 7. Branch Scoping Rules

| Entity | Scoping rule | Where enforced |
| :--- | :--- | :--- |
| Invoice | Branch-bound users only see their branch; owner sees all | Dashboard queries, ticket/receipt closures, pickup |
| Order | Production queue and actions scoped through `invoice.branch_id` | `Production`, `OrderService` callers |
| Payment | Cashier queue and expense lists scoped by `branch_id` | `Cashier::render()` |
| Expense | Scoped by `branch_id` | `Cashier`, owner reporting, manager dashboard |
| Material | Global catalog; stock per branch. Manager writes target the client-supplied `branchId`, not a server-derived branch. | `MaterialManagement`, `InventoryService` |
| Machine | `branch_id` null = global; manager sees own branch | `MachineManagement` |
| Attribute | Branch-bound records protected on edit/delete | `Manager` |
| Pricing | Branch-scoped rows; manager writes own branch | `Manager`, `PricingService` |
| Stock ledger | Scoped by branch | `Manager`, `InventoryService` |
| Activity log | Owner views all; rows carry the actor's branch | `Owner` audit tab |

Behavior for a user with `branch_id = null`:

- Ticket and receipt closures skip the branch comparison, so the user can open any invoice.
- Production, cashier, and manager queries fall back to the first branch (`Branch::value('id')`) when no branch is assigned.
- This is intended for owners but is not role-checked at the query level.

---

## 8. Enforcement Points in the Code

| Layer | Mechanism | Coverage |
| :--- | :--- | :--- |
| Route middleware | `auth`, `verified` on `/dashboard`; `auth` on `/profile` | `/dashboard`, `/profile` |
| Route closure | Explicit branch comparison | `/orders/{invoice}/ticket`, `/orders/{invoice}/receipt` |
| Component `mount()` | Role allow-list with `abort(403)` | Branch, category, machine management |
| Component action | Inline role or branch assertion | Machine, material, category, manager pricing/attributes, production start/complete, cashier pickup, user management (mode-dependent) |
| Service layer | Mostly trusts the caller; `InventoryService` and `ProductCategoryService` read `auth()` for logging and defaults | Partial |
| Middleware alias | `RoleMiddleware` registered as `role` | Not used by any route |
| View layer | Role dispatch for dashboards and sidebar links | Enforced for navigation, not a security boundary by itself |

There are no Laravel Gates or Policies defined for these resources. Authorization is inline and component-specific.

---

## 9. Known Access Gaps

1. **Cashier invoice selection lacks a branch assertion.** `selectInvoice()` and `processCheckout()` load an invoice by primary key. A crafted Livewire call with another branch's invoice ID is not explicitly rejected. The listing that feeds the UI is branch-scoped.
2. **Dashboard components do not assert role in `mount()`.** A user sees only their role's dashboard through `dashboard.blade.php`, but the dashboard components themselves have no role guard. `UserManagement` and `MaterialManagement` also lack mount-time role guards; `BranchManagement`, `CategoryManagement`, and `MachineManagement` do assert.
3. **Ticket and receipt access is role-agnostic.** Any authenticated user in the invoice's branch can print both documents.
4. **`branch_id = null` behaves as cross-branch access.** This is intended for owners, but the routes and queries do not verify that the user is an owner before skipping branch scoping.
5. **No per-record authorization for designers.** The order list is branch-scoped but does not restrict a designer to invoices they personally created.
6. **No payment reversal authority.** No role can void or refund a payment because the workflow does not exist.
7. **Manager user-management restrictions are bypassable.** `UserManagement::$mode` is client-writable and the component has no `mount()` role guard; setting `mode=owner` exposes all users and skips the manager checks.
8. **Material management is not branch-isolated.** `MaterialManagement::$branchId` is client-writable, and a manager can delete a global material used by other branches.
9. **User detail lookup is not branch-scoped.** `UserManagement::openDetailModal()` loads any user by ID after the component is open.
10. **The production waste tab does not render.** `Production::render()` queries a non-existent `materials.branch_id`, so the tab fails before the user can log waste.
11. **Category deletion is destructive.** It hard deletes the category and cascades to products, materials, and pricing, unless NO ACTION references from `wasted_materials` or `stock_ledgers` block the delete.
