# Newspaper Distribution Management System

## Software Project Plan and Architecture Document

Version: 1.0  
Prepared for: Newspaper Distributor  
Recommended stack: Laravel latest stable version, Inertia.js, Vue 3, PostgreSQL

---

## 1. Executive Summary

The Newspaper Distribution Management System will replace Excel-based daily dispatch, return, payment, and reporting workflows with a transaction-safe web application.

The system will manage shops, newspapers, historical prices, daily invoices, returns, payment collections, forecasting, reporting, printing, imports, exports, users, roles, and audit logs.

The most important architectural principle is financial immutability: invoices must preserve the price and quantity context at the time they are created. Later price changes, returns, or payment activity must be recorded as separate business events, not by rewriting invoice history.

---

## 2. Business Context

The distributor delivers newspapers daily to many shops. Tomorrow's dispatch invoices are usually prepared today using previous sales and returns as a guide.

Current Excel limitations:

- Manual calculations are error-prone.
- Price changes are difficult to preserve historically.
- Returns and payments can accidentally overwrite prior values.
- Daily and monthly reports require repeated spreadsheet work.
- Outstanding balances are hard to track reliably.
- Auditability is weak.
- Expansion to multiple routes, agents, or branches would be difficult.

Primary system goals:

- Preserve financial history.
- Reduce manual calculation effort.
- Improve daily dispatch planning.
- Track shop-wise balances.
- Enable printable billing notes and invoices.
- Support Excel import/export during migration and operations.
- Provide a clean foundation for future scaling.

---

## 3. Technology Stack

Backend:

- Laravel latest stable version at project start.
- PHP 8.3+ or latest supported PHP version for the selected Laravel version.
- Laravel Sanctum for authenticated SPA sessions if needed.
- Laravel Policies and Gates for authorization.
- Laravel Queues for imports, exports, report generation, and notifications.
- Laravel Scheduler for recurring report snapshots and backups.

Frontend:

- Inertia.js.
- Vue 3 Composition API.
- TypeScript recommended.
- Tailwind CSS or selected design system.
- Pinia only if cross-page client state becomes necessary.

Database:

- PostgreSQL.
- Native `numeric` columns for money.
- UUIDs optional, but `bigint` primary keys are simpler and efficient for this domain.
- JSONB for audit metadata, import logs, and flexible report filters.

Architecture style:

- SOLID principles.
- Service Layer Architecture.
- Repository Pattern.
- Clean Code.
- Domain-oriented modules.
- Transaction-safe financial workflows.

---

## 4. High-Level Architecture

Recommended request flow:

```text
Browser
  -> Inertia.js + Vue 3 Pages
  -> Laravel Controllers
  -> Form Requests / DTOs
  -> Application Services
  -> Domain Services
  -> Repositories
  -> Eloquent Models
  -> PostgreSQL
```

Supporting layers:

```text
Jobs / Queues
  -> Imports, exports, report generation, notifications

Events / Listeners
  -> Audit logging, balance updates, invoice issued events

Policies
  -> Role and permission enforcement

Reports
  -> Query services, materialized views, exports, printable PDFs
```

Core rule: controllers should coordinate HTTP concerns only. Business rules belong in services. Database querying belongs in repositories or reporting query classes.

---

## 5. Recommended Modules

Core modules:

- Authentication and Authorization
- Shops
- Newspapers
- Price History
- Dispatch / Invoices
- Returns
- Payments / Collections
- Forecasting
- Reports
- Printing
- Invoice Delivery
- Excel Import / Export
- Audit Logs
- Administration / Settings

Future modules:

- Route Management
- Delivery Agents
- Vehicle Assignment
- Supplier / Publisher Billing
- Mobile Collection App
- SMS / WhatsApp Notifications
- Multi-branch Support

---

## 6. Functional Requirements

### 6.1 Shop Management

- Create, update, and deactivate shops.
- Store shop code, name, owner/contact, address, route, credit limit, and opening balance.
- View shop balance, invoices, returns, payments, and outstanding amount.
- Prevent deletion if financial history exists.

### 6.2 Newspaper Management

- Create and maintain newspapers.
- Store name, code, publisher, language, publication frequency, and active status.
- Support daily, weekly, Sunday-only, or custom schedules.

### 6.3 Price History Management

- Add price records with effective dates.
- Prevent overlapping price periods for the same newspaper.
- Automatically use the correct price for invoice date.
- Preserve old invoice item prices even after future price changes.

### 6.4 Daily Dispatch / Invoice Creation

- Create tomorrow's invoices today.
- Select dispatch date.
- Generate shop-wise newspaper quantities.
- Allow manual adjustment before confirmation.
- Snapshot unit price into invoice item.
- Print invoice or billing note.
- Lock confirmed invoices from casual editing.

### 6.5 Forecasting

- Suggest tomorrow quantities based on historical sales.
- Use previous week, same weekday, average net sales, returns, and manual overrides.
- Allow users to accept or edit suggestions.
- Store forecast source and final chosen quantity.

### 6.6 Return Management

- Record unsold returns against shop, newspaper, and business date.
- Optionally link return lines to original invoice lines.
- Returns create return records or credit notes.
- Returns must not edit invoice quantities or invoice totals directly.

### 6.7 Payment Collection

- Record cash, bank transfer, cheque, card, or other payment methods.
- Allocate payment to invoices automatically or manually.
- Track unallocated payments.
- Track cheque status if cheques are used.
- Generate collection reports.

### 6.8 Reports

- Daily dispatch report.
- Daily sales report.
- Daily return report.
- Daily collection report.
- Shop ledger.
- Outstanding payment report.
- Monthly sales report.
- Newspaper-wise sales report.
- Route-wise sales report.
- Price history report.
- Audit report.

### 6.9 Invoice Printing and Delivery

- Printable invoice/billing note format.
- Shop-wise invoice.
- Daily route bundle printing.
- PDF generation.
- Optional thermal or A5 format.
- Send invoice to shop by email.
- Send invoice to shop by WhatsApp using a provider integration.
- Support delivery options from the invoice screen: `Print`, `Email`, and `WhatsApp`.
- Store invoice delivery history, including channel, recipient, delivery status, sent user, and timestamp.
- Allow reprint/resend without changing invoice financial data.
- Allow shop-level preferred invoice delivery method.
- Validate that email address or WhatsApp phone number exists before sending.
- Queue email and WhatsApp sending so the invoice screen remains fast.

### 6.10 Excel Import / Export

- Import shops.
- Import newspapers.
- Import opening balances.
- Import historical dispatch data if available.
- Export daily/monthly reports.
- Export outstanding balances.
- Validate imports before applying.

### 6.11 User Authentication and Roles

- Login/logout.
- Password reset.
- Role-based permissions.
- Optional two-factor authentication.
- Activity tracking.

### 6.12 Audit Logs

- Track create, update, delete, confirm, cancel, payment, return, import, and export actions.
- Store actor, timestamp, entity type, entity id, before/after values, IP, and user agent.

---

## 7. Non-Functional Requirements

Reliability:

- Financial operations must be ACID transaction-safe.
- Confirmed invoices, returns, and payments should be immutable except through controlled reversal/correction workflows.

Performance:

- Daily invoice generation should support hundreds or thousands of shops.
- Reports should load within acceptable time using indexes, summary tables, or materialized views.
- Heavy exports should run in queues.

Security:

- Role-based access control.
- Server-side validation.
- CSRF protection.
- Input sanitization.
- Audit trails for financial changes.

Maintainability:

- SOLID services.
- Repository interfaces.
- Feature tests for financial workflows.
- Clear module boundaries.
- Strict naming conventions.

Scalability:

- Separate read/reporting queries from write workflows.
- Use queues for slow tasks.
- Prepare for route, branch, and multi-distributor expansion.

Usability:

- Fast daily invoice entry.
- Keyboard-friendly quantity editing.
- Bulk generation and printing.
- Clear outstanding balance visibility.

---

## 8. Recommended Database Schema

### 8.1 Master Tables

#### shops

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| code | varchar unique | Human-friendly shop code |
| name | varchar | Shop name |
| owner_name | varchar nullable | Owner/contact person |
| phone | varchar nullable | Contact |
| email | varchar nullable | Invoice email recipient |
| whatsapp_phone | varchar nullable | Invoice WhatsApp recipient |
| preferred_invoice_delivery | varchar nullable | print/email/whatsapp |
| address | text nullable | Address |
| route_id | bigint nullable fk | Future route support |
| credit_limit | numeric(12,2) default 0 | Optional control |
| opening_balance | numeric(12,2) default 0 | Initial receivable |
| status | varchar | active/inactive |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |
| deleted_at | timestamp nullable | Soft delete |

#### newspapers

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| code | varchar unique | Newspaper code |
| name | varchar | Newspaper name |
| publisher_name | varchar nullable | Publisher |
| language | varchar nullable | Language |
| frequency | varchar | daily/weekly/sunday/custom |
| status | varchar | active/inactive |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |
| deleted_at | timestamp nullable | Soft delete |

#### newspaper_prices

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| newspaper_id | bigint fk | Newspaper |
| price | numeric(10,2) | Selling price |
| cost_price | numeric(10,2) nullable | Optional margin tracking |
| effective_from | date | Start date |
| effective_to | date nullable | End date |
| created_by | bigint fk users | Creator |
| created_at | timestamp | Laravel timestamp |

#### routes

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| code | varchar unique | Route code |
| name | varchar | Route name |
| status | varchar | active/inactive |

### 8.2 Invoice Tables

#### invoices

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| invoice_no | varchar unique | Generated sequence |
| shop_id | bigint fk | Shop |
| route_id | bigint nullable fk | Snapshot/helper |
| invoice_date | date | Business invoice date |
| dispatch_date | date | Delivery date |
| status | varchar | draft/confirmed/printed/cancelled |
| gross_total | numeric(12,2) | Sum of line totals |
| return_total | numeric(12,2) default 0 | Derived/posted value |
| net_total | numeric(12,2) | Gross minus approved returns if business wants |
| paid_total | numeric(12,2) default 0 | Allocated payment total |
| balance_total | numeric(12,2) | Net minus paid |
| notes | text nullable | Internal notes |
| prepared_by | bigint fk users | Creator |
| confirmed_by | bigint nullable fk users | Confirmer |
| confirmed_at | timestamp nullable | Confirmation time |
| printed_at | timestamp nullable | Print time |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |

#### invoice_items

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| invoice_id | bigint fk | Invoice |
| newspaper_id | bigint fk | Newspaper |
| newspaper_code | varchar | Snapshot |
| newspaper_name | varchar | Snapshot |
| quantity | integer | Dispatched quantity |
| unit_price | numeric(10,2) | Snapshot price |
| line_total | numeric(12,2) | quantity * unit_price |
| forecast_quantity | integer nullable | Suggested quantity |
| manual_adjustment_reason | text nullable | Override reason |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |

Important: `invoice_items.unit_price`, `newspaper_code`, and `newspaper_name` are snapshots and must not be recalculated from current newspaper data.

#### invoice_deliveries

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| invoice_id | bigint fk | Invoice |
| channel | varchar | print/email/whatsapp |
| recipient | varchar nullable | Email address or WhatsApp number |
| status | varchar | pending/sent/delivered/failed/cancelled |
| provider | varchar nullable | Mail driver or WhatsApp provider |
| provider_message_id | varchar nullable | External provider reference |
| error_message | text nullable | Failure reason |
| sent_by | bigint nullable fk users | User who triggered delivery |
| sent_at | timestamp nullable | Send timestamp |
| delivered_at | timestamp nullable | Delivery confirmation timestamp |
| metadata | jsonb nullable | Payload summary, template, file path |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |

Invoice delivery records are operational/audit records. They must never modify invoice totals, invoice items, or historical prices.

### 8.3 Return Tables

#### returns

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| return_no | varchar unique | Generated sequence |
| shop_id | bigint fk | Shop |
| return_date | date | Date returned |
| business_date | date | Related sales/dispatch date |
| status | varchar | draft/approved/cancelled |
| total_amount | numeric(12,2) | Return total |
| created_by | bigint fk users | Creator |
| approved_by | bigint nullable fk users | Approver |
| approved_at | timestamp nullable | Approval time |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |

#### return_items

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| return_id | bigint fk | Return |
| invoice_item_id | bigint nullable fk | Optional invoice item link |
| newspaper_id | bigint fk | Newspaper |
| quantity | integer | Returned quantity |
| unit_price | numeric(10,2) | Snapshot from invoice if linked |
| line_total | numeric(12,2) | quantity * unit_price |
| reason | varchar nullable | unsold/damaged/other |
| created_at | timestamp | Laravel timestamp |

Returns should create separate negative financial effects. They should never rewrite original invoice item quantities.

### 8.4 Payment Tables

#### payments

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| payment_no | varchar unique | Generated sequence |
| shop_id | bigint fk | Shop |
| payment_date | date | Collection date |
| amount | numeric(12,2) | Payment amount |
| method | varchar | cash/bank/cheque/card/other |
| status | varchar | received/cleared/bounced/cancelled |
| reference_no | varchar nullable | Bank reference or cheque number |
| notes | text nullable | Internal notes |
| collected_by | bigint fk users | Collector |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |

#### payment_allocations

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| payment_id | bigint fk | Payment |
| invoice_id | bigint fk | Invoice |
| amount | numeric(12,2) | Amount allocated |
| created_at | timestamp | Laravel timestamp |

This enables partial payments and one payment covering multiple invoices.

### 8.5 Forecasting Tables

#### dispatch_forecasts

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| shop_id | bigint fk | Shop |
| newspaper_id | bigint fk | Newspaper |
| forecast_date | date | Dispatch date |
| suggested_quantity | integer | System suggestion |
| final_quantity | integer nullable | User accepted/edited value |
| method | varchar | same_weekday/moving_average/manual |
| confidence_score | numeric(5,2) nullable | Optional confidence |
| source_data | jsonb | Historical values |
| created_at | timestamp | Laravel timestamp |

### 8.6 Reporting / Ledger Tables

#### shop_ledger_entries

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| shop_id | bigint fk | Shop |
| entry_date | date | Business date |
| source_type | varchar | invoice/return/payment/opening_adjustment |
| source_id | bigint | Source record id |
| debit | numeric(12,2) default 0 | Invoice increases receivable |
| credit | numeric(12,2) default 0 | Payment/return reduces receivable |
| balance_after | numeric(12,2) nullable | Optional running balance |
| description | text nullable | Human-readable description |
| created_at | timestamp | Laravel timestamp |

Ledger entries are strongly recommended. They make outstanding reports cleaner and auditable.

### 8.7 System Tables

#### users

Use Laravel's default users table plus status fields.

#### roles and permissions

Recommended package: Spatie Laravel Permission.

Tables:

- `roles`
- `permissions`
- `model_has_roles`
- `role_has_permissions`

#### audit_logs

| Column | Type | Notes |
|---|---|---|
| id | bigserial pk | Primary key |
| actor_id | bigint nullable fk users | Acting user |
| action | varchar | created/updated/deleted/confirmed/cancelled |
| auditable_type | varchar | Model class/entity |
| auditable_id | bigint nullable | Entity id |
| old_values | jsonb nullable | Previous values |
| new_values | jsonb nullable | New values |
| metadata | jsonb nullable | IP, user agent, request id |
| created_at | timestamp | Laravel timestamp |

#### imports

Suggested columns:

- id
- type
- file_name
- status
- total_rows
- success_rows
- failed_rows
- error_file_path
- created_by
- created_at
- updated_at

#### exports

Suggested columns:

- id
- type
- filters jsonb
- file_path
- status
- created_by
- created_at
- updated_at

---

## 9. ER Diagram Description

Main relationships:

- One `shop` has many `invoices`.
- One `shop` has many `returns`.
- One `shop` has many `payments`.
- One `shop` has many `shop_ledger_entries`.
- One `route` has many `shops`.
- One `newspaper` has many `newspaper_prices`.
- One `invoice` has many `invoice_items`.
- One `newspaper` has many `invoice_items`.
- One `return` has many `return_items`.
- One `return_item` may reference one `invoice_item`.
- One `payment` has many `payment_allocations`.
- One `invoice` has many `payment_allocations`.
- One `shop` and one `newspaper` can have many `dispatch_forecasts`.
- One `user` can prepare invoices, approve returns, collect payments, and create audit logs.

Text ER sketch:

```text
routes 1--N shops
shops 1--N invoices 1--N invoice_items N--1 newspapers
newspapers 1--N newspaper_prices
shops 1--N returns 1--N return_items N--1 newspapers
return_items N--0/1 invoice_items
shops 1--N payments 1--N payment_allocations N--1 invoices
shops 1--N shop_ledger_entries
shops 1--N dispatch_forecasts N--1 newspapers
users 1--N audit_logs
```

---

## 10. Laravel Project Folder Structure

Recommended structure:

```text
app/
  Domain/
    Shops/
      Models/
      Data/
      Enums/
      Services/
      Repositories/
      Actions/
      Policies/
    Newspapers/
    Pricing/
    Invoices/
    Returns/
    Payments/
    Forecasting/
    Reports/
    Imports/
    Audit/
  Http/
    Controllers/
      Shop/
      Newspaper/
      Invoice/
      Return/
      Payment/
      Report/
    Requests/
    Resources/
    Middleware/
  Services/
    Pdf/
    Excel/
    Numbering/
  Support/
    Money/
    Dates/
    QueryFilters/
  Jobs/
  Events/
  Listeners/
  Exceptions/
database/
  migrations/
  seeders/
resources/
  js/
    Pages/
    Components/
    Layouts/
    Composables/
    Types/
routes/
  web.php
  auth.php
tests/
  Feature/
  Unit/
```

Alternative: keep Laravel defaults and use `app/Modules/*` if the team prefers module isolation. For a serious business system, domain-based folders are cleaner than placing every service in a flat `app/Services`.

---

## 11. Service Layer Structure

Recommended application services:

- `ShopService`
- `NewspaperService`
- `PriceHistoryService`
- `InvoiceGenerationService`
- `InvoiceConfirmationService`
- `InvoicePrintingService`
- `ReturnApprovalService`
- `PaymentCollectionService`
- `PaymentAllocationService`
- `ForecastingService`
- `DailyReportService`
- `MonthlyReportService`
- `OutstandingReportService`
- `InvoiceDeliveryService`
- `InvoiceEmailService`
- `InvoiceWhatsAppService`
- `ExcelImportService`
- `ExcelExportService`
- `AuditLogService`
- `LedgerService`

Service responsibilities:

- Validate business rules after request validation.
- Coordinate repositories.
- Open database transactions.
- Dispatch domain events.
- Create ledger entries.
- Keep controllers thin.

Example:

```php
final class InvoiceConfirmationService
{
    public function confirm(int $invoiceId, int $userId): Invoice
    {
        return DB::transaction(function () use ($invoiceId, $userId) {
            $invoice = $this->invoices->lockForUpdate($invoiceId);

            $this->guard->ensureCanConfirm($invoice);

            $invoice = $this->invoices->confirm($invoice, $userId);

            $this->ledger->recordInvoice($invoice);

            event(new InvoiceConfirmed($invoice));

            return $invoice;
        });
    }
}
```

---

## 12. Repository Structure

Repository interfaces:

- `ShopRepositoryInterface`
- `NewspaperRepositoryInterface`
- `NewspaperPriceRepositoryInterface`
- `InvoiceRepositoryInterface`
- `InvoiceItemRepositoryInterface`
- `ReturnRepositoryInterface`
- `PaymentRepositoryInterface`
- `InvoiceDeliveryRepositoryInterface`
- `LedgerRepositoryInterface`
- `ForecastRepositoryInterface`
- `ReportRepositoryInterface`
- `AuditLogRepositoryInterface`

Repository implementations:

- `EloquentShopRepository`
- `EloquentNewspaperRepository`
- `EloquentInvoiceRepository`
- `EloquentInvoiceDeliveryRepository`
- `EloquentPaymentRepository`
- `PostgresReportRepository`

Guidelines:

- Use repositories for complex queries and persistence boundaries.
- Avoid hiding every simple Eloquent call behind unnecessary abstraction.
- Reporting repositories may use query builder, CTEs, materialized views, or raw SQL where appropriate.
- Services should depend on interfaces where it improves testing or future replacement.

---

## 13. API / Module Boundaries

Because Inertia is used, most routes are web routes returning Inertia pages, not a separate public API.

Recommended module boundaries:

- `Shops` owns shop profile and status.
- `Newspapers` owns newspaper identity.
- `Pricing` owns price history and price lookup.
- `Invoices` owns dispatch invoice lifecycle.
- `Returns` owns return lifecycle and credit calculation.
- `Payments` owns collections and allocations.
- `Ledger` owns receivable movements.
- `Reports` reads from invoices, returns, payments, and ledger but does not mutate them.
- `InvoiceDelivery` owns printing, email sending, WhatsApp sending, delivery logs, and provider status updates.
- `Imports` validates and delegates to domain services.
- `Audit` listens to important events and records history.

Route examples:

```php
Route::resource('shops', ShopController::class);
Route::resource('newspapers', NewspaperController::class);
Route::resource('newspaper-prices', NewspaperPriceController::class);

Route::get('dispatch/create', [DispatchController::class, 'create']);
Route::post('dispatch/forecast', [DispatchController::class, 'forecast']);
Route::post('invoices', [InvoiceController::class, 'store']);
Route::post('invoices/{invoice}/confirm', [InvoiceConfirmationController::class, 'store']);
Route::post('invoices/{invoice}/print', [InvoicePrintController::class, 'store']);
Route::post('invoices/{invoice}/send-email', [InvoiceEmailController::class, 'store']);
Route::post('invoices/{invoice}/send-whatsapp', [InvoiceWhatsAppController::class, 'store']);
Route::get('invoices/{invoice}/deliveries', [InvoiceDeliveryController::class, 'index']);

Route::resource('returns', ReturnController::class);
Route::post('returns/{return}/approve', [ReturnApprovalController::class, 'store']);

Route::resource('payments', PaymentController::class);

Route::get('reports/daily-sales', DailySalesReportController::class);
Route::get('reports/monthly-sales', MonthlySalesReportController::class);
Route::get('reports/outstanding', OutstandingReportController::class);
```

---

## 14. Invoice Workflow

Status flow:

```text
draft -> confirmed -> printed
draft -> cancelled
confirmed -> cancelled_by_reversal
printed -> reprinted
```

Workflow:

1. User selects dispatch date, usually tomorrow.
2. System loads active shops and newspapers available for that date.
3. Forecasting service suggests quantities.
4. User reviews and manually adjusts quantities.
5. System fetches effective newspaper price for dispatch date.
6. System creates invoice and invoice items in `draft`.
7. User confirms invoice.
8. Confirmation locks business values and creates ledger debit.
9. User chooses delivery option: print, email, or WhatsApp.
10. System generates the invoice PDF or printable view from immutable invoice snapshot data.
11. System records an `invoice_deliveries` entry for every print, email, or WhatsApp attempt.
12. Email and WhatsApp deliveries are processed through queued jobs.
13. Any later correction uses cancellation/reversal, not silent editing.

Important rules:

- Draft invoices may be edited.
- Confirmed invoices should not allow direct line edits.
- Confirmed invoice totals must match item snapshots.
- Invoice number should be generated only once and remain stable.
- Reprinting should not change invoice contents.
- Resending by email or WhatsApp should not change invoice contents.
- Delivery failures should be logged and retried without duplicating financial records.

### 14.1 Invoice Delivery Options

Print:

- Generate a browser printable view or PDF.
- Support single invoice printing.
- Support route-wise batch printing.
- Record print activity in `invoice_deliveries`.

Email:

- Send invoice PDF as an attachment or secure link.
- Use shop email by default and allow authorized one-time override.
- Queue sending through Laravel jobs.
- Record provider status and error messages.

WhatsApp:

- Send invoice PDF or invoice link through a WhatsApp Business API provider.
- Use shop WhatsApp number by default.
- Queue sending through Laravel jobs.
- Record provider message id and delivery callback status where supported.

Recommended delivery UI:

- Show buttons: `Print`, `Email`, `WhatsApp`.
- Disable `Email` if the shop has no email address.
- Disable `WhatsApp` if the shop has no WhatsApp phone number.
- Show latest delivery status and full delivery history.

---

## 15. Return Workflow

Status flow:

```text
draft -> approved -> posted_to_ledger
draft -> cancelled
approved -> reversed
```

Workflow:

1. User selects shop and return date.
2. User enters returned newspapers and quantities.
3. System optionally links items to invoice items from the related business date.
4. System uses invoice item price if linked, otherwise effective historical price for that business date.
5. User saves return as draft.
6. Authorized user approves return.
7. Approval creates ledger credit.
8. Return totals are reflected in reports and outstanding balance.
9. Original invoice quantity remains unchanged.

Important rules:

- Return quantity cannot exceed dispatched quantity if linked to an invoice item.
- Return approval should be transaction-safe.
- Approved returns should be corrected through reversal, not editing.

---

## 16. Payment Collection Workflow

Status flow:

```text
draft/received -> cleared
received -> cancelled
received -> bounced
cleared -> reversed
```

Workflow:

1. Collector selects shop.
2. System shows outstanding invoices and balance.
3. Collector enters amount, date, method, reference, and notes.
4. System validates amount and method-specific details.
5. Payment is saved.
6. Allocation can be automatic FIFO against oldest invoices or manual.
7. Ledger credit is created.
8. Invoice `paid_total` and `balance_total` are updated or derived from allocations.
9. Outstanding report updates immediately.

Recommended default allocation:

- Allocate to oldest confirmed unpaid invoices first.
- Keep remaining amount as unallocated credit if overpaid.
- Allow admin to reallocate if needed.

---

## 17. Forecasting Logic

MVP forecasting:

```text
suggested_quantity = max(0, average(last_4_same_weekday_net_sales) + safety_buffer)
```

Where:

```text
net_sales = dispatched_quantity - returned_quantity
```

Useful strategies:

- Same weekday comparison: compare Monday with previous Mondays.
- Moving average: last 7 or 14 business days.
- Weighted average: recent days get higher weight.
- Return-aware forecast: reduce quantity if return percentage is high.
- Minimum quantity rule: keep shop-specific minimum supply.
- Manual override: user can adjust and reason is stored.
- Seasonal overrides: public holidays, special editions, events.
- Confidence score: high if consistent sales, low if volatile.

MVP formula:

```text
base = average(net_sales for same shop/newspaper/same weekday over last 4 weeks)
return_rate = average(returns / dispatched for last 4 matching days)
suggestion = round(base * adjustment_factor)
```

Example adjustment:

```text
if return_rate > 0.25, reduce by 10%
if return_rate < 0.05 and sell_out_detected, increase by 5% or 1 copy
```

Future forecasting:

- Per-shop newspaper demand trends.
- Holiday calendar.
- Weather or event-based adjustments.
- ML model after enough clean history exists.

---

## 18. PostgreSQL Design Recommendations

- Use `numeric(12,2)` for financial amounts.
- Use `date` for business dates and `timestamp with time zone` for audit/system timestamps.
- Use `check` constraints for positive quantities and non-negative money.
- Use PostgreSQL enums cautiously. Laravel string-backed PHP enums plus `check` constraints are easier to evolve.
- Use `jsonb` for audit metadata, import validation errors, and forecast source data.
- Use generated columns only where they simplify read-heavy calculations and do not conflict with Laravel portability.
- Use materialized views for heavy monthly reports if live query performance becomes slow.
- Use database transactions for invoice confirmation, return approval, and payment allocation.
- Use row-level locks for invoices/payments during allocation or reversal.

---

## 19. Recommended Indexes

```sql
CREATE INDEX idx_shops_status ON shops(status);
CREATE INDEX idx_shops_route_id ON shops(route_id);
CREATE INDEX idx_newspapers_status ON newspapers(status);

CREATE INDEX idx_newspaper_prices_lookup
ON newspaper_prices(newspaper_id, effective_from, effective_to);

CREATE INDEX idx_invoices_shop_date ON invoices(shop_id, invoice_date);
CREATE INDEX idx_invoices_dispatch_date ON invoices(dispatch_date);
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE UNIQUE INDEX idx_invoices_invoice_no ON invoices(invoice_no);

CREATE INDEX idx_invoice_items_invoice_id ON invoice_items(invoice_id);
CREATE INDEX idx_invoice_items_newspaper_id ON invoice_items(newspaper_id);
CREATE INDEX idx_invoice_deliveries_invoice_id ON invoice_deliveries(invoice_id);
CREATE INDEX idx_invoice_deliveries_channel_status ON invoice_deliveries(channel, status);

CREATE INDEX idx_returns_shop_date ON returns(shop_id, return_date);
CREATE INDEX idx_returns_business_date ON returns(business_date);
CREATE INDEX idx_return_items_invoice_item_id ON return_items(invoice_item_id);

CREATE INDEX idx_payments_shop_date ON payments(shop_id, payment_date);
CREATE INDEX idx_payment_allocations_invoice_id ON payment_allocations(invoice_id);
CREATE INDEX idx_payment_allocations_payment_id ON payment_allocations(payment_id);

CREATE INDEX idx_ledger_shop_date ON shop_ledger_entries(shop_id, entry_date);
CREATE INDEX idx_ledger_source ON shop_ledger_entries(source_type, source_id);

CREATE INDEX idx_audit_logs_entity ON audit_logs(auditable_type, auditable_id);
CREATE INDEX idx_audit_logs_actor_date ON audit_logs(actor_id, created_at);
```

Advanced PostgreSQL recommendations:

- Add exclusion constraint to prevent overlapping newspaper price periods.
- Add partial indexes for active shops/newspapers.
- Add composite report indexes after observing real query plans.

---

## 20. Transaction Handling Strategy

Use `DB::transaction()` for:

- Invoice creation with items.
- Invoice confirmation and ledger debit.
- Return approval and ledger credit.
- Payment creation, allocation, and ledger credit.
- Reversal/cancellation workflows.
- Import apply step.

Use `lockForUpdate()` for:

- Invoice rows during confirmation, cancellation, allocation, or reversal.
- Payment rows during allocation changes.
- Shop ledger balance calculation if storing running balance.

Recommended transaction rules:

- Never call external APIs inside a database transaction.
- Generate PDFs after transaction commits.
- Dispatch queued jobs after commit.
- Keep transactions short.
- Recalculate totals server-side, never trust frontend totals.
- Use idempotency keys for repeated submit protection.

---

## 21. Coding Standards

PHP/Laravel:

- Use typed properties and return types.
- Use Laravel Form Requests for validation.
- Use DTOs for service inputs where requests become complex.
- Use PHP backed enums for statuses and methods.
- Use Laravel Policies for authorization.
- Use Laravel Pint for formatting.
- Use PHPStan or Larastan for static analysis.
- Prefer constructor injection.
- Avoid business logic in controllers, models, or Vue pages.
- Use feature tests for workflows and unit tests for calculation services.

Vue/Inertia:

- Use Vue 3 Composition API.
- Use TypeScript.
- Keep pages as composition containers.
- Extract reusable form/table components.
- Keep financial calculations server-authoritative.
- Use optimistic UI only for non-financial interactions.

Testing standards:

- Test price history lookup.
- Test invoice snapshots preserve old prices.
- Test returns do not mutate invoices.
- Test partial payments.
- Test overpayments.
- Test outstanding report totals.
- Test role restrictions.

---

## 22. Security Considerations

Authentication:

- Use a Laravel authentication starter kit compatible with Inertia + Vue.
- Enforce strong passwords.
- Optional email verification.
- Optional two-factor authentication for admins.

Authorization:

- Roles: admin, manager, billing_clerk, collector, viewer, auditor.
- Use permissions for fine-grained control.
- Restrict cancellation/reversal to authorized users.

Data security:

- Validate all inputs server-side.
- Protect against mass assignment.
- Use CSRF protection.
- Sanitize imported Excel data.
- Store uploaded files outside public path.
- Use signed URLs for generated exports where appropriate.

Financial security:

- Require reason for cancellation/reversal.
- Audit all financial changes.
- Prevent direct editing of confirmed records.
- Use database constraints as a second line of defense.
- Restrict invoice email/WhatsApp sending to authorized users.
- Avoid exposing public invoice PDFs without signed URLs or authentication.
- Log every invoice print/send action with recipient and user.
- Do not include sensitive customer data in WhatsApp messages beyond what is required.
- Validate and normalize phone numbers before WhatsApp sending.

Operational security:

- Enforce HTTPS.
- Secure `.env`.
- Rotate app keys/secrets carefully.
- Limit production database access.
- Log failed logins and sensitive actions.

---

## 23. Backup Strategy

Database backups:

- Daily full PostgreSQL backup.
- Point-in-time recovery using WAL archiving if possible.
- Keep at least 30 daily backups.
- Keep monthly backups for 12 months.
- Test restore process monthly.

File backups:

- Backup uploaded imports.
- Backup generated exports and invoices if they must be retained.
- Store backups in S3-compatible storage or secure offsite location.

Application backups:

- Keep source code in Git.
- Keep deployment artifacts reproducible.
- Document restore procedure.

Minimum restore plan:

```text
Provision server -> deploy code -> restore database -> restore storage -> run migrations -> verify login/report totals
```

---

## 24. Deployment Architecture

Recommended production setup:

```text
User Browser
  -> HTTPS / Nginx
  -> PHP-FPM Laravel App
  -> PostgreSQL
  -> Redis
  -> Queue Worker
  -> Scheduler
  -> Object Storage / Local Secure Storage
```

Components:

- Nginx as web server.
- PHP-FPM for Laravel.
- PostgreSQL as primary database.
- Redis for cache, queues, and locks.
- Supervisor/systemd for queue workers.
- Laravel Scheduler via cron.
- SSL certificate via Let's Encrypt or managed platform.
- Optional Laravel Forge, Laravel Cloud, or VPS deployment.

Environment separation:

- Local
- Staging
- Production

Deployment checklist:

- Run tests.
- Build frontend assets.
- Run migrations.
- Cache config/routes/views.
- Restart queue workers.
- Smoke test invoice creation, payment, and reports.

---

## 25. Future Scalability Suggestions

Business scalability:

- Multi-route support.
- Multi-branch support.
- Multiple collectors.
- Shop credit limits and alerts.
- Publisher settlement module.
- Delivery agent mobile app.
- Barcode or QR invoice tracking.
- WhatsApp invoice sending.

Technical scalability:

- Move heavy reports to materialized views.
- Add read replica for reporting.
- Use queued exports for large Excel files.
- Add Redis locks around batch invoice generation.
- Use event-driven ledger updates.
- Add API layer for mobile apps.
- Introduce tenant boundaries if multiple distributors use the same platform.

---

## 26. Development Phase Breakdown

### Phase 1: Foundation

- Laravel + Inertia + Vue setup.
- Authentication.
- Roles and permissions.
- Base layout.
- Audit logging foundation.
- PostgreSQL schema foundation.

### Phase 2: Master Data

- Shop management.
- Newspaper management.
- Price history.
- Route setup.
- Excel import for shops/newspapers.

### Phase 3: Invoicing

- Forecast-assisted invoice creation.
- Draft/confirm workflow.
- Invoice item snapshots.
- Invoice printing.
- Invoice delivery by email and WhatsApp.
- Daily dispatch report.

### Phase 4: Returns

- Return entry.
- Return approval.
- Ledger credits.
- Return reports.

### Phase 5: Payments

- Payment collection.
- Payment allocation.
- Shop ledger.
- Outstanding reports.

### Phase 6: Reporting

- Daily sales reports.
- Monthly sales reports.
- Newspaper-wise reports.
- Route-wise reports.
- Export to Excel/PDF.

### Phase 7: Hardening

- Audit review screens.
- Backup automation.
- Performance tuning.
- Security testing.
- User acceptance testing.

---

## 27. MVP Scope

Recommended MVP:

- Login and roles.
- Shop CRUD.
- Newspaper CRUD.
- Price history.
- Daily invoice creation.
- Manual quantity entry.
- Basic forecasting using previous same weekday.
- Invoice confirmation.
- Invoice printing.
- Invoice delivery options: print, email, and WhatsApp.
- Return entry and approval.
- Payment collection.
- Outstanding report.
- Daily sales report.
- Monthly sales report.
- Excel import for shops/newspapers.
- Excel export for key reports.
- Audit logs for financial actions.

Defer from MVP:

- Advanced ML forecasting.
- Mobile app.
- Multi-branch.
- Publisher settlement.
- SMS/WhatsApp integration.
- Complex cheque lifecycle unless immediately required.
- Read replicas/materialized views unless data volume demands them.

---

## 28. Future Enhancements

- Mobile collection app.
- Delivery route optimization.
- Shop credit scoring.
- Automatic low/high quantity alerts.
- Holiday-aware forecasting.
- Advanced WhatsApp delivery templates and provider callbacks.
- Customer invoice portal with secure invoice links.
- Customer portal for shops.
- Publisher purchase and settlement tracking.
- Profit margin reports using cost price.
- Dashboard with KPIs.
- Multi-language invoice templates.
- Offline-first collection mode.
- Integration with accounting software.

---

## 29. Risks and Technical Challenges

Key risks:

- Historical Excel data may be inconsistent.
- Price history reconstruction may be difficult if old prices were not tracked.
- Users may expect confirmed invoices to remain editable like Excel.
- Forecasting accuracy depends on clean return and sales data.
- Financial correction workflows must be carefully designed.
- Reports can become slow if every result is calculated from raw rows without indexing.
- Printing formats may require multiple iterations.
- Payment allocation rules must match real business practice.

Mitigation:

- Start with a clear import validation process.
- Use reversible financial records.
- Train users on draft vs confirmed states.
- Implement audit logs early.
- Build reports from ledger entries where possible.
- Run UAT with real daily workflows before full launch.

---

## 30. Naming Conventions

Database:

- Tables: plural snake_case, e.g. `invoice_items`.
- Columns: snake_case, e.g. `invoice_date`.
- Foreign keys: singular model name plus `_id`, e.g. `shop_id`.
- Indexes: `idx_{table}_{columns}`.
- Unique indexes: `uniq_{table}_{columns}`.

Laravel:

- Models: singular PascalCase, e.g. `InvoiceItem`.
- Services: `{Domain}Service`, e.g. `PaymentCollectionService`.
- Repositories: `{Model}RepositoryInterface`, `Eloquent{Model}Repository`.
- Form Requests: `{Action}{Entity}Request`, e.g. `StoreInvoiceRequest`.
- Enums: `{Entity}{Field}Enum`, e.g. `InvoiceStatus`.
- Events: past tense, e.g. `InvoiceConfirmed`.
- Jobs: imperative, e.g. `GenerateMonthlyReport`.
- Policies: `{Model}Policy`.

Frontend:

- Pages: `Invoices/Create.vue`, `Reports/DailySales.vue`.
- Components: PascalCase, e.g. `InvoiceLineEditor.vue`.
- Composables: `useInvoiceForm.ts`.
- Types: `Invoice.ts`, `Shop.ts`.

---

## 31. Suggested Enums

`ShopStatus`:

- `active`
- `inactive`
- `blocked`

`NewspaperStatus`:

- `active`
- `inactive`
- `discontinued`

`PublicationFrequency`:

- `daily`
- `weekly`
- `sunday`
- `custom`

`InvoiceStatus`:

- `draft`
- `confirmed`
- `printed`
- `cancelled`
- `reversed`

`InvoiceDeliveryChannel`:

- `print`
- `email`
- `whatsapp`

`InvoiceDeliveryStatus`:

- `pending`
- `sent`
- `delivered`
- `failed`
- `cancelled`

`ReturnStatus`:

- `draft`
- `approved`
- `cancelled`
- `reversed`

`PaymentMethod`:

- `cash`
- `bank_transfer`
- `cheque`
- `card`
- `other`

`PaymentStatus`:

- `received`
- `cleared`
- `bounced`
- `cancelled`
- `reversed`

`LedgerSourceType`:

- `opening_balance`
- `invoice`
- `return`
- `payment`
- `adjustment`
- `reversal`

`AuditAction`:

- `created`
- `updated`
- `deleted`
- `confirmed`
- `approved`
- `printed`
- `cancelled`
- `reversed`
- `imported`
- `exported`
- `login`
- `logout`

---

## 32. Suggested Status Flows

Invoice:

```text
draft -> confirmed -> printed
draft -> cancelled
confirmed -> reversed
printed -> reprinted
```

Invoice Delivery:

```text
pending -> sent -> delivered
pending -> failed
sent -> failed
failed -> pending
pending -> cancelled
```

Return:

```text
draft -> approved
draft -> cancelled
approved -> reversed
```

Payment:

```text
received -> cleared
received -> bounced
received -> cancelled
cleared -> reversed
```

Import:

```text
uploaded -> validating -> validated -> processing -> completed
uploaded -> validating -> failed
processing -> completed_with_errors
```

Export:

```text
requested -> processing -> ready
processing -> failed
```

---

## 33. Suggested Database Constraints

Recommended constraints:

```sql
ALTER TABLE invoice_items
ADD CONSTRAINT chk_invoice_items_quantity_positive CHECK (quantity >= 0);

ALTER TABLE invoice_items
ADD CONSTRAINT chk_invoice_items_unit_price_non_negative CHECK (unit_price >= 0);

ALTER TABLE return_items
ADD CONSTRAINT chk_return_items_quantity_positive CHECK (quantity > 0);

ALTER TABLE payments
ADD CONSTRAINT chk_payments_amount_positive CHECK (amount > 0);

ALTER TABLE payment_allocations
ADD CONSTRAINT chk_payment_allocations_amount_positive CHECK (amount > 0);

ALTER TABLE newspaper_prices
ADD CONSTRAINT chk_newspaper_prices_price_non_negative CHECK (price >= 0);

ALTER TABLE invoices
ADD CONSTRAINT chk_invoice_totals_non_negative
CHECK (gross_total >= 0 AND net_total >= 0 AND paid_total >= 0 AND balance_total >= 0);

ALTER TABLE invoice_deliveries
ADD CONSTRAINT chk_invoice_deliveries_channel
CHECK (channel IN ('print', 'email', 'whatsapp'));

ALTER TABLE invoice_deliveries
ADD CONSTRAINT chk_invoice_deliveries_status
CHECK (status IN ('pending', 'sent', 'delivered', 'failed', 'cancelled'));
```

Recommended uniqueness:

- `shops.code`
- `newspapers.code`
- `invoices.invoice_no`
- `returns.return_no`
- `payments.payment_no`

Recommended price-period rule:

- Prevent overlapping price periods per newspaper.
- Ensure `effective_to` is null or greater than/equal to `effective_from`.
- Allow only one open-ended current price per newspaper.

---

## 34. Reporting Architecture

Recommended reporting design:

- Use live query services for simple daily reports.
- Use ledger table for outstanding balances.
- Use queued jobs for large monthly Excel exports.
- Use materialized views for heavy monthly summaries if needed.
- Store generated report files with filters and creator metadata.
- Keep reporting read-only; reports must not mutate business data.

Report data sources:

```text
Daily Dispatch Report -> invoices + invoice_items
Daily Sales Report -> invoices + returns + payments
Monthly Sales Report -> invoice_items grouped by month/shop/newspaper
Outstanding Report -> shop_ledger_entries or invoices/payment_allocations
Collection Report -> payments + payment_allocations
Return Report -> returns + return_items
```

Recommended report filters:

- Date range.
- Shop.
- Route.
- Newspaper.
- Payment method.
- Status.
- Collector.
- Export format.

---

## 35. Final Recommendation

Build the MVP around three immutable financial events: `Invoice`, `Return`, and `Payment`. Use a `ShopLedgerEntry` table as the accounting backbone. Keep invoices historically frozen, record returns separately, snapshot newspaper prices into invoice and return lines, and wrap every financial workflow in a PostgreSQL transaction.

That gives the distributor a system that feels simple day-to-day, but has the bones of a serious accounting-grade business application underneath.
