Note: backend folder is laravel project , customer_app folder is flutter customer app , driver_app folder is flutter driver app

# Multi-Service Ride, Delivery & Freight Platform — Software Requirements Specification

**Document type:** Product SRS + Technical Blueprint + Implementation Brief for Claude Opus 5.5  
**Version:** 1.0  
**Status:** Implementation source of truth  
**Initial market defaults:** Egypt, Arabic (Egyptian/Modern Standard Arabic wording as configured), EGP  
**Required clients:** Customer Flutter app, Driver/Service Provider Flutter app, Laravel Admin Panel, Laravel Public Website, Laravel REST API/backend  
**Product direction:** An original, inDrive-inspired peer-to-peer transport marketplace with customer-proposed fares, provider accept/counter-offers, and customer choice. Do not use inDrive's name, logo, proprietary graphics, exact copy, or attempt a pixel-for-pixel reproduction.

---

## 0. Instructions to Claude Opus 5.5 — Read This First

You are acting as a **Senior Product Engineer, Senior Flutter Engineer, Senior Laravel Architect, Senior UI/UX Engineer, QA Lead, and Security Reviewer**. Use this document as the product and acceptance specification.

### Mandatory working rules

1. **Inspect before changing.** Start by inspecting the repository, current branches, file structure, framework/package versions, environment examples, migrations, existing screens, routes, tests, and git status. Identify what exists and what is missing. Do not overwrite working code or discard user changes.
2. **Create a repository-specific implementation plan.** Put it in `docs/IMPLEMENTATION_PLAN.md`, with milestones, dependency order, files/modules expected to change, test commands, and risks. Also maintain `docs/IMPLEMENTATION_STATUS.md` after every milestone.
3. **Build functioning product flows, not just screens.** Every visible control must work or be clearly presented as disabled with a reason. Avoid dead buttons, fake API success, mock production data, placeholder-only modules, or unimplemented navigation. Mock data is allowed only in explicitly identified development/demo mode.
4. **Use the stack already established in the repo where it is sound.** Prefer stable versions compatible with the repository and verify actual installed versions before updating dependencies. Do not upgrade major packages without a written reason and tests.
5. **Do not ask broad clarification questions.** Make sensible, configurable defaults from this SRS, record assumptions in `docs/DECISIONS.md`, and continue. Never hard-code market-specific business policy when it can be a database setting.
6. **Work in vertical slices.** For each feature, complete schema/migration, backend business logic, authorization, API validation, client integration, loading/empty/error states, translations, tests, and documentation before calling the feature done.
7. **Keep business rules on the server.** Fare negotiation, trip transitions, coupon eligibility, commission, wallet balances, cancellation fees, and driver eligibility must be validated server-side. Never trust totals or statuses supplied by a Flutter client.
8. **Keep secrets out of source control.** Use `.env.example` with documented placeholders. Never place real API keys, payment credentials, Firebase private keys, signing keys, OTP secrets, or production database credentials in source code or commits.
9. **Run checks after each milestone.** Run relevant automated tests, formatter/linter, static analysis, and build checks. Report exact commands and results. Do not claim a test or build passed unless it was actually run successfully.
10. **Preserve delivery quality.** Add migrations and seeders, API documentation, setup instructions, queue and scheduler instructions, deployment guidance, observability, backup guidance, and a clear list of required external integrations.
11. **Never weaken security to make tests pass.** Do not disable authorization, validation, TLS assumptions, signature checks, or OTP checks. If external credentials are unavailable, implement an adapter and a safe local development provider; document what needs production credentials.
12. **Do not stop after creating a plan.** Complete the project in milestones within the current execution environment. If the environment or available time prevents completion, finish the most complete, testable vertical slice possible and state precisely what remains.

### First deliverables

- `docs/IMPLEMENTATION_PLAN.md`
- `docs/IMPLEMENTATION_STATUS.md`
- `docs/DECISIONS.md`
- `docs/ARCHITECTURE.md`
- `docs/API.md` or generated OpenAPI documentation
- Working implementation, migrations, tests, seeders, and setup instructions

### Definition of done for every feature

A feature is done only when its permission rules, validation, database behavior, error handling, audit/logging requirements, Arabic and English labels, RTL layout behavior, automated tests, and relevant documentation are complete. A UI screenshot alone is not implementation.

---

## 1. Product Vision and Core Experience

Build a multi-service mobility marketplace that lets customers request a ride, courier, or cargo/furniture transport using **the fare they propose**. Nearby eligible drivers/service providers can:

- Accept the customer's proposed fare.
- Send a counter-offer with a different fare.
- Decline/skip the request without being punished merely for declining a request.

The customer sees offers together in a comparison experience and chooses one based on **fare, provider rating, vehicle, estimated arrival, completed trips, and relevant service capabilities**. Once the customer selects an offer, a booking is confirmed and the other offers are closed or invalidated. The selected provider navigates to pickup, marks arrival, and completes the job through controlled lifecycle transitions. The customer can track the trip live, share trip details, contact the provider, use safety tools, pay, rate, tip where supported, and report an issue.

This bid-and-choose model is the product's defining feature. It is not a conventional dispatch-only app where the backend silently chooses a driver and sets a mandatory dynamic fare.

### Product principles

- **Customer choice:** customers propose a fare and compare available offers.
- **Provider choice:** providers can accept, counter-offer, or skip requests.
- **Transparent agreement:** the accepted offer is a binding price snapshot for the booking, subject only to explicitly accepted add-ons/changes.
- **No forced surge pricing:** do not automatically increase the displayed fare solely because demand is high. Admins may configure reference estimates, minimum fares, limits, warnings, and supply incentives, but a customer's proposed fare and provider's counter-offer remain explicit.
- **Many service types, one account:** rides, tuk-tuks, taxis, courier, furniture moving, and freight use common accounts and booking infrastructure with service-specific forms and lifecycle steps.
- **Safety by design:** identity/document checks, trip PIN/OTP, live-location sharing, trusted contacts, incident reporting, privacy controls, and audit logs.
- **Arabic first:** Arabic is the default language; the first launch must feel fully native in Arabic and RTL, not like a translated English app.
- **Configurable operations:** services, vehicle classes, service areas, fare limits, commissions, cancellation rules, coupon policies, and payment methods should be configurable from admin without a code deployment where practical.

### Reference behavior

Use these public descriptions as high-level references for the concept of proposing fares, receiving counter-offers, choosing a provider, and multi-service transport. They are reference material only; this specification defines our own original product:

- inDrive listing and feature summary: https://apps.apple.com/eg/app/indrive-rides-at-your-price/id780125801
- inDrive passenger flow overview: https://blog.indrive.com/lo-la/article/passengers-101-how-to-use-our-app
- inDrive driver flow overview: https://blog.indrive.com/en-za/article/drivers-101-how-to-use-our-app
- inDrive freight price-offer concept: https://indriver.com/mobile/page/offer_freight/en/in

---

## 2. Platforms and Scope

### 2.1 Required deliverables

1. **Customer App — Flutter / Android + iOS**
   - Phone authentication, profile, service selection, booking creation, fare proposal, offer comparison, live trip tracking, messages/calls, coupons, payments, trip history, ratings, support, trusted contacts, safety toolkit, saved places, settings, Arabic/English.
2. **Driver App — Flutter / Android + iOS**
   - Driver onboarding, document uploads, vehicle/service setup, verification status, online/offline availability, incoming requests, accept/counter-offer/skip, customer selection, navigation, pickup/arrival/start/complete workflow, job-specific delivery proof, earnings, wallet, payouts, incentives, ratings, support, safety, account/documents.
3. **Admin Panel — Laravel**
   - Secure role-based operations center for service configuration, users, drivers, vehicles, verification, bookings, live trip operations, offers, coupons, finance, commissions, wallets, payouts, disputes, safety, content, service areas, analytics, audit logs, and system configuration.
4. **Public Website — Laravel**
   - Arabic-first responsive, SEO-friendly website with English support, product/service landing pages, service-area pages, How It Works, become-a-driver/provider flows, FAQs, contact/support, help articles, policies, app-download links, company information, optional booking entry point, and admin-managed CMS content.
5. **Backend — Laravel REST API**
   - Domain logic, database, authentication, authorization, pricing/negotiation, dispatch fan-out, real-time events, notifications, payments, coupon validation, trip state machine, finance ledger, reporting, and integration adapters.

### 2.2 Recommended architecture

Start as a **modular monolith** unless the repository already has a sound alternative. Avoid premature microservices. Use clear domain boundaries, queues, and integration adapters so modules can be separated later if needed.

Recommended logical modules:

- Identity & Access
- Customer Profiles
- Provider/Driver Onboarding
- Vehicles & Service Capabilities
- Service Catalog & Service Areas
- Geospatial Availability & Dispatch
- Booking & Fare Negotiation
- Live Tracking & Realtime Events
- Messaging & Calling Coordination
- Payments & Payment Methods
- Coupon & Promotion Engine
- Wallet, Commission & Provider Earnings
- Notifications
- Reviews & Reputation
- Safety & Incident Management
- Support & Disputes
- Admin & Audit
- CMS & Public Website
- Analytics & Reports
- Platform Configuration

The public website and admin panel may share the Laravel application and domain layer while using separate layouts, route groups, and middleware. Keep public site rendering separate from admin APIs. Mobile clients use `/api/v1/...` JSON APIs. Use explicit API versioning.

### 2.3 Out of scope for initial launch unless already in the repo

- Owning or operating a physical vehicle fleet.
- Building a restaurant/merchant marketplace with merchant inventory and checkout. Grocery/merchant delivery may be added as a later service family.
- Insurance underwriting or legal/compliance decisions performed automatically by the platform.
- Guaranteed third-party map, SMS, email, payment, or telephony services without provider credentials.
- Reproducing another company's brand, logo, exact copy, or proprietary screens.

---

## 3. Market, Locale, Currency, and Configuration Defaults

- Default country: Egypt.
- Default locale: `ar`.
- Secondary locale: `en`.
- Default currency: `EGP`.
- Phone input: international format with country picker; Egypt is preselected, not the only allowed country.
- Default timezone: configurable by city/country; Egypt timezone should be configurable rather than a hard-coded value in business logic.
- Arabic must use right-to-left direction for the overall interface, menus, forms, sheets, navigation bars, text alignment, and relevant icons. Map coordinate conventions do not change for RTL.
- English must render left-to-right with no broken layout.
- Translations must be stored in locale resources or managed content. Do not put user-visible strings directly in widgets/controllers.
- Currency formatting, phone rules, distance units, date/time formats, tax rules, and legal texts must be configurable per market.
- Support Arabic names and addresses, Arabic-Indic and Western digits as a display preference if feasible, and proper Unicode input/storage.
- The app should remember a user's chosen language; new installs default to Arabic. Language changes must not require a new account.

---

## 4. User Types, Roles, and Permissions

### 4.1 Public/product roles

- **Guest:** can view public website, services, FAQs, policies, and driver recruitment pages. Guest booking is optional; default booking requires OTP-authenticated account.
- **Customer:** requests rides/delivery/freight, negotiates fares, chooses providers, manages payments/coupons, tracks bookings, rates providers, opens support and safety reports.
- **Driver / Service Provider:** completes onboarding, registers one or more vehicles subject to policy, chooses eligible service types, goes online, responds to requests, performs jobs, manages earnings and payout requests.
- **Recipient:** a delivery recipient identified by name/phone/OTP; does not need an account unless the sender invites them.
- **Support Agent:** handles customer/driver tickets and disputes within assigned permissions.

### 4.2 Admin roles (least privilege)

Implement permissions as granular abilities, not only role-name checks:

- Super Admin
- Operations Admin
- Dispatch/Live Operations Agent
- Driver Verification Officer
- Customer Support Agent
- Safety & Incident Officer
- Finance Manager
- Finance Payout Officer
- Marketing/Coupon Manager
- Content/CMS Editor
- Analyst/Read-only Auditor
- System/Integration Administrator

Examples of permissions:

- `dashboard.view`
- `customers.view`, `customers.update`, `customers.suspend`
- `drivers.view`, `drivers.verify`, `drivers.reject`, `drivers.suspend`
- `vehicles.view`, `vehicles.approve`, `vehicles.reject`
- `services.manage`, `vehicle_categories.manage`, `service_areas.manage`
- `bookings.view`, `bookings.manage`, `bookings.cancel`, `bookings.assign_override`
- `offers.view`, `offers.moderate`
- `coupons.view`, `coupons.manage`
- `finance.view`, `finance.reconcile`, `payouts.review`, `payouts.approve`
- `wallets.adjust` (dual-control and mandatory reason)
- `incidents.view`, `incidents.manage`, `incidents.export_restricted`
- `support.manage`
- `content.manage`, `translations.manage`
- `reports.view`, `reports.export`
- `settings.view`, `settings.manage`
- `audit_logs.view`

Dangerous operations need audit records, reason fields, and sometimes dual approval. Do not allow regular support staff to edit financial ledgers or view unnecessary sensitive document contents.

---

## 5. Service Catalog: Multi-Vehicle and Multi-Job Platform

The service catalog must be **data-driven**. Admins can activate/deactivate categories, define vehicle types, define fields, set service-area availability, configure fare bounds, commissions, dimensions/capacities, booking rules, and whether scheduled bookings or counter-offers are supported. Adding a vehicle/service type should not require hard-coded Flutter branching throughout the app.

### 5.1 Initial service families

#### A. City passenger rides

- Tuk-tuk / auto-rickshaw (where locally permitted)
- Motorcycle ride (only if enabled and compliant in the configured market)
- Economy car
- Standard sedan
- Comfort/premium car
- Taxi
- Family car / SUV
- 6–7 passenger vehicle
- Minivan / microbus
- Accessible vehicle / wheelchair-friendly vehicle (only where supply exists and requirements are validated)

Each vehicle category needs configurable passenger capacity, luggage capacity, seat-belt notes, allowed baggage, service areas, fare floors/ceilings, provider requirements, and map marker/icon.

#### B. City-to-city / intercity rides

- Sedan
- Comfort/premium car
- Family/SUV
- Van/minibus
- Other admin-enabled classes

Capture origin, destination, departure date/time, passenger count, luggage count, optional return trip, stops, and customer fare proposal. Scheduled/intercity offers may arrive over a longer window than on-demand requests. Clearly show provider offers and cancellation policy before acceptance.

#### C. Courier / parcel delivery

- Documents/small parcel by motorcycle
- Small parcel by car
- Medium parcel
- Large parcel / van delivery

Capture pickup/drop-off contacts, package size/weight, package category, declared value where relevant, fragile flag, notes, pickup/drop-off photos if configured, recipient OTP, proof of delivery, delivery attempts, and chain-of-custody events. Clearly prohibit illegal/restricted goods through admin-managed policy and sender acknowledgement.

#### D. Furniture moving / home and office moving

- Pickup truck
- Light truck
- Box truck
- Van
- Small/medium/large moving vehicle
- Vehicle plus one or more helpers/loaders

Capture items (structured checklist plus free text), approximate dimensions/volume/weight, photos/video where allowed, building floor at origin and destination, elevator availability, stairs, walking distance from vehicle to door, packing needs, disassembly/reassembly, number of helpers, loading/unloading, fragile items, extra stops, preferred time/window, and access/parking notes. The offer must state whether it includes vehicle only, driver assistance, movers, packing, stairs, assembly, tolls, or other extras. Never silently add these costs after confirmation.

#### E. Cargo / freight / commercial transport

- Pickup / single-cab truck
- 1-ton, 2-ton, 3-ton and admin-defined payload classes
- Light/medium/heavy truck
- Covered/box truck
- Refrigerated or specialist vehicle only when configured and verified

Capture cargo type, load/unload requirements, estimated weight and dimensions, loading equipment, origin/destination, pickup window, stops, contact persons, documents, goods value/insurance declarations where relevant, and delivery proof. Only display capacities supported by the vehicle's approved configuration.

#### F. Optional future service families (must be extensible, not necessarily fully implemented in the first milestone)

- Hourly hire / multi-stop errand
- School/staff commute and repeat bookings
- Airport transfer
- Tow truck / roadside assistance
- Grocery/merchant pickup
- Bus/large group transport
- Medical/non-emergency transport only after applicable policy, provider requirements, and compliance review

### 5.2 Service definition schema

Each service/category configuration should include:

- `code`, localized `name`, localized `description`, `family`, icon, sort order, active flag.
- Allowed booking types: on-demand, scheduled, intercity, parcel, freight, hourly.
- Allowed vehicle categories.
- Form schema / required attributes; validation rules; localized labels and help text.
- Max passengers, parcel weight/size, cargo weight/volume, and other category-specific limits.
- Fare negotiation enabled/disabled; customer reference estimate enabled/disabled; offer expiry and dispatch radius settings.
- Minimum/maximum customer proposed fare and provider counter-offer policy.
- Commission rule, cancellation policy, wait-time policy, no-show policy, toll/parking policy.
- Payment methods, coupon eligibility, tipping eligibility, service hours, service-area eligibility.
- Required driver documents, vehicle documents, training flags, and verification checks.
- Whether a trip PIN is required, proof photos are required, recipient OTP is required, or extra milestones are required.

Use validated structured metadata rather than trusting arbitrary client JSON. Version service forms/policies so a booking retains the configuration snapshot that applied when created.

---

## 6. Customer App — Navigation and Modules

### 6.1 Main navigation

Suggested Arabic-first navigation (names editable in CMS):

- Home / الخدمات
- My Trips / رحلاتي
- Wallet & Payments / المحفظة والدفع
- Offers / العروض
- Account / حسابي

Keep active booking status visible from the home screen and use a persistent trip card/banner while the customer has an active booking.

### 6.2 Onboarding and authentication

1. Splash screen and language choice; Arabic is selected by default.
2. Intro slides can be skipped; do not force a long tour.
3. Request location permission with a clear explanation only when location is needed. Manual location entry must remain possible if permission is denied.
4. Phone number entry with country selector.
5. OTP verification with resend cooldown, attempt limits, anti-abuse limits, and safe error messages.
6. Create/update profile: display name, optional photo, phone, email optional, preferred language, accessibility needs optional, emergency contact optional.
7. Terms/privacy consent version stored with timestamp, locale, and policy version.
8. Account state checks: active, restricted, suspended, verification required, deleted/deletion pending.
9. Persist authentication securely using platform secure storage; never store OTP or plaintext passwords.

Email/social sign-in may be added but phone OTP is the primary login flow. Provide account deletion request and personal-data export where required by local law and platform policy.

### 6.3 Home and service selection

- Map-centered home with pickup position, destination field, current location indicator, service family selector, and saved places.
- Search address by text, map pin, recent address, saved Home/Work, or contact-shared location if supported.
- Pickup and destination can be swapped.
- Allow multiple stops only for service types that support them.
- Show available service categories based on current service area and provider supply; avoid displaying unavailable options as bookable.
- Service cards show category, short localized description, capacity/cargo capability, and a price reference range only if the backend has a valid estimate.
- Handle GPS disabled, denied permission, no network, geocoder errors, map provider outage, and outside-service-area state.
- Never pretend to know an exact address if the location provider only returns approximate coordinates.

### 6.4 Booking creation and customer fare offer

The booking form must be service-specific, but share a consistent flow:

1. Confirm pickup and destination or route.
2. Fill in service-specific details (passengers, bags, cargo, parcel, furniture, helper count, pickup time, stops, notes, contacts, etc.).
3. Display a transparent reference estimate/range, if available, showing what affects the estimate. Label it as a guide, not a mandatory fare.
4. Customer enters a proposed fare. Provide optional quick suggestions based on configured guidance, previous fare history if permitted, and route estimate. The customer must be able to edit the fare within configured bounds.
5. Apply a coupon, choose payment method, and view a clear pre-request summary. If the coupon is applied to the customer total, show the discount separately; it must not silently rewrite the provider's agreed compensation.
6. Confirm request; backend creates a request and distributes it to eligible providers.
7. Customer sees the request in a “finding offers” state and receives real-time offers.

Validation: origin/destination, service area, category, service details, passenger/cargo constraints, customer account status, valid pricing range, coupon eligibility, and payment policy are all checked server-side. The submitted price is stored in currency minor units or precise decimal strategy; choose one approach consistently and document it.

### 6.5 Offer list and negotiation UX — core inDrive-inspired behavior

Display offer cards in real time. Each offer should show:

- Provider display name and profile photo if available.
- Rating and number of completed jobs.
- Vehicle type, make/model, color, plate number according to local privacy policy.
- Provider's offered fare, clearly distinguish customer-proposed fare vs provider counter-offer.
- Estimated pickup arrival time and provider distance when reliable.
- Relevant capability badges: cargo capacity, helpers, child seat, accessibility, or other verified attributes.
- Cancellation/no-show notices that matter before selection.
- Optional profile/trip-history signals that are useful and permitted.
- Actions: select provider, reject/dismiss offer, view profile details. Avoid exposing another customer's private data.

Rules:

- The first valid provider offer is not automatically assigned unless the customer explicitly enables a separately designed “auto-accept exact fare” preference.
- The customer chooses the provider. Selection must be atomic: a booking can have no more than one selected/confirmed provider.
- A provider can accept the proposed fare or counter-offer one or more times up to configurable limits, but all offers must expire when the request expires or is assigned.
- Customer may accept a counter-offer by choosing it. Do not quietly accept for them.
- If the customer changes the route or major service requirements after offers begin, invalidate existing offers or request explicit provider re-confirmation.
- Show a countdown/expiry for each offer when applicable; do not imply that an expired offer is still available.
- When one provider is chosen, inform the selected provider and close/notify other providers that the request is no longer available.
- Display clear no-offer state with retry/edit-price/cancel choices. Never promise that raising the fare guarantees an offer.
- The system may use configured fairness warnings such as “Your price is lower than recent offers,” but must not silently modify the user's price.

### 6.6 Booking confirmation and live tracking

Once the provider is selected:

- Show provider identity, verified vehicle details, agreed fare, payment method, pickup/drop-off, ETA, call/chat options, cancel rules, and safety controls.
- Show progress statuses: provider confirmed, heading to pickup, arrived, job started, in progress, completed.
- On map, show pickup/drop-off pins and provider live location when allowed. Smooth marker updates without distorting the actual coordinate. Show last-updated time and stale-location warning when necessary.
- Provide “Share trip” link/details with trusted contacts or system share sheet; shared access should expire and reveal only necessary data.
- Provide emergency/help action appropriate to policy. It should open an actual configured safety workflow, not merely a decorative button.
- Allow customer to contact provider using an in-app relay/controlled contact method when possible; direct phone number exposure should be configurable.
- Offer route progress and trip events. Do not show an exact route or ETA as guaranteed.
- For ride services, use a pickup verification PIN/OTP by default or a configurable safety policy. Provider cannot start the trip until successful verification, except a permissioned emergency override requiring a reason and audit trail.
- For deliveries/freight, show pickup collected, in transit, delivery attempt, delivered, recipient OTP/proof-of-delivery status and relevant timeline.
- If GPS updates are lost, retain last known position and explicitly mark it stale. Do not fabricate movement.

### 6.7 Completion, payment, rating, and rebooking

After completion:

- Show agreed base fare, accepted add-ons, coupon discount, platform/customer fees, tax if applicable, tip, payment method, and final amount with clear definitions.
- No additional cost can be added after agreement unless the customer explicitly approves a permitted change or the original offer terms clearly defined the variable item (e.g. tolls charged at cost, with proof where required).
- Complete online payment or mark cash collection according to the payment workflow. Payment failure creates a recoverable payment state; do not mark a payment as paid without provider confirmation/webhook or a verified cash confirmation rule.
- Ask for a rating and optional private feedback; use separate structured questions where useful (professionalism, vehicle quality, communication, delivery condition).
- Let customer report a safety issue or dispute without being forced to rate first.
- Allow optional tip according to market/payment configuration; do not make the tip preselected by default.
- Show receipt, booking ID, route, duration/distance when available, payment status, coupon usage, and support entry.
- Offer repeat booking and saved route.

### 6.8 Customer profile and account modules

- Profile/contact details and profile photo.
- Preferred language; Arabic default.
- Saved places and recent addresses.
- Favorite/recent providers if allowed by product policy.
- Trusted contacts/emergency contacts.
- Payment methods, wallet balance, transaction history, refund history.
- Coupons and promotion history.
- Booking history with search/filter by service/status/date.
- Ratings received? Do not show internal moderation fields.
- Help center, tickets, dispute status, safety history appropriate to privacy policy.
- Notification preferences by channel/category.
- Privacy controls, data export/deletion request, blocked contact/provider if supported.
- App version, terms, privacy policy, permissions explanation, logout.

---

## 7. Driver / Service Provider App — Modules and Lifecycle

### 7.1 Provider onboarding lifecycle

Expected states:

`draft → phone_verified → profile_submitted → documents_pending → under_review → approved → online_eligible`

Possible branches: `rejected` (with reason and resubmission), `more_information_required`, `temporarily_suspended`, `permanently_suspended`, `expired_documents`, `deletion_pending`.

Required flow:

1. Phone OTP registration.
2. Name, date of birth where required, profile photo, contact details, language, service location/city.
3. Select provider profile type: individual driver, courier, moving crew/driver, commercial transport provider, company/fleet. Company/fleet features may be staged but data model should not block them.
4. Select services they want to provide.
5. Create one or more vehicles with type/category, make/model/year, color, registration/plate, passenger/cargo capacity, photos, special capabilities, and approved service categories.
6. Upload required documents with issue/expiry dates, status, and rejection reason. Examples are configurable per country/service: driving licence, national ID, vehicle registration, insurance, permits, inspection certificate, commercial/freight permits, and company documentation.
7. Review declarations and agreements; store accepted version/time.
8. Submit application and display verification progress, pending tasks, expiry dates, and support contact.
9. Admin verifies/requests corrections/approves/rejects. Driver receives a reason and next action.
10. Only approved drivers with valid required documents, an approved active vehicle, configured eligibility, and no blocking account flags can go online.

Do not claim a legal document is mandatory everywhere. Requirements must be configurable by region and service type and reviewed for local compliance.

### 7.2 Driver home and availability

- Online/offline switch with clear eligibility reason if unavailable.
- Current selected vehicle/service modes; a provider should not receive incompatible jobs.
- Map of current location and active job.
- Incoming request feed with sound/vibration preferences and accessibility support.
- Daily/weekly earnings snapshot, completed jobs, rating, current wallet balance, support link, and safety controls.
- Offline mode stops dispatch; any active booking has a safe, explicit workflow before going offline.
- Location collection occurs only as needed for availability, approaching pickup, active trip, and explicitly explained safety features. Stop high-frequency tracking when offline, except a clearly disclosed permitted background feature.

### 7.3 Incoming request cards

Show only necessary information before accepting/offer:

- Service category and required vehicle capability.
- Pickup area and approximate distance to pickup; avoid unnecessary customer exact-location disclosure before booking selection if policy requires masking.
- Destination/route or enough information to make a sound price decision, with full details disclosed before final acceptance as appropriate to service.
- Customer proposed fare, relevant estimated distance/duration, service details, parcel/cargo/furniture summary, scheduled time, and any helper/add-on needs.
- Actions: accept proposed fare, counter-offer, skip/decline.

Drivers should not lose ratings merely for skipping unsuitable requests. Separate legitimate abuse detection from normal choice. Any penalties for cancellations after accepting/being chosen must be transparent, proportionate, configurable, and appealable.

### 7.4 Counter-offer workflow

- Driver enters a proposed counter-fare and optional note/inclusions for service types that need scope clarification.
- Validate minimum/maximum rules and currency. Display any platform commission estimate and estimated net earning before submission where policy permits.
- Driver can withdraw an offer while the customer has not accepted it, subject to clear rules.
- Offer can expire; driver sees timer/status.
- No provider may alter a submitted fare silently. Any revision is a new offer version with an audit entry.
- For furniture/freight, counter-offer must be able to include a structured scope summary: vehicle class, helpers, packing, stairs, loading equipment, waiting, extra stops, toll/parking treatment, and exclusions. Both sides must see the same accepted summary.

### 7.5 After the customer selects the driver

- Provider receives confirmation with accepted amount, service details, customer contact approach, pickup address, destination, payment policy, and cancellation terms.
- Provider confirms/acknowledges dispatch, then app guides to pickup.
- Buttons and permitted transitions are explicit: `Navigate to pickup`, `Arrived`, `Start job`, `Complete job`. Enforce transitions server-side.
- `Arrived` records timestamp and GPS evidence where available. A configurable free-wait period can begin.
- Before starting a passenger ride, verify customer pickup PIN/OTP or allowed alternative. For parcel/freight, verify pickup contact/PIN and record collected item details or photos if required.
- During job, report issue, contact support, use emergency workflow, and view route/customer instructions.
- Complete job only when required service-specific evidence is provided (recipient OTP, photo/signature, weight or checklist where configured). If recipient is unavailable, use `delivery_attempt_failed` or `needs_support`, not fake completion.
- At completion, provider sees amount, commission/fees, net earning, payment status and any payout impact.

### 7.6 Earnings, wallet, payouts, and incentives

- Earnings overview by day/week/month and by service category.
- Booking-level breakdown: gross fare, platform commission, discounts funded by platform/provider, tax/fees, tip, net provider earning, cash collected, adjustments, and payment status.
- Wallet ledger with immutable transaction records and balances derived/reconciled from ledger entries.
- Payout method enrollment and verification, payout requests, min/max amount, fees, review status, rejection reason, transfer reference, and history.
- Cash jobs must reconcile commission/platform fee owed; do not let cash jobs bypass platform ledger rules.
- Support different commercial models through configuration: percentage commission, fixed fee, service-specific fee, campaigns/waivers, or hybrid. Avoid embedding a single rate in code.
- Incentives can include bonuses for completed qualifying jobs, time/location campaigns, referral bonuses, and quests; every campaign must define eligibility, budget, start/end, caps, fraud rules, and ledger posting.
- Driver statements/export should be available where applicable.

### 7.7 Driver account modules

- Profile and verification status.
- Vehicles and service capabilities.
- Documents with expiry reminders and resubmission.
- Earnings, wallet, payout methods, transactions, commissions, incentives.
- Job history and detailed receipts.
- Ratings and feedback (moderated safely; do not expose reporter identity in sensitive cases).
- Support tickets/disputes and appeal forms.
- Notification settings, language, privacy settings, availability rules.
- Emergency/trip safety settings and trusted contacts where applicable.
- Pause/deactivate/delete account request.

---

## 8. Booking, Offer, and Trip State Machines

Implement explicit state machines in the backend and test allowed transitions. Use database transactions, locking/idempotency where multiple providers/customer actions can race.

### 8.1 Booking states

Recommended canonical statuses (adapt naming to repository but keep semantics):

- `draft`
- `searching_offers`
- `offers_received`
- `provider_selected` / `confirmed`
- `provider_heading_to_pickup`
- `provider_arrived`
- `in_progress`
- `delivery_attempt_failed` (service-dependent)
- `completed_pending_payment` (if payment is not settled at completion)
- `completed`
- `cancelled_by_customer`
- `cancelled_by_provider`
- `cancelled_by_operations`
- `expired_no_provider`
- `disputed`
- `refunded` / `partially_refunded` as finance outcomes, not replacements for trip history

Not all statuses are available for every service. Model trip/booking lifecycle separately from payment, dispute, and refund status when that is more accurate.

### 8.2 Offer states

- `submitted`
- `counter_offered`
- `accepted_by_customer`
- `rejected_by_customer`
- `withdrawn_by_provider`
- `expired`
- `invalidated_request_changed`
- `invalidated_provider_unavailable`
- `lost_booking_assigned`

Rules:

- Only one active provider assignment per booking.
- Customer acceptance of an offer and booking assignment must be atomic.
- Offer amount/currency/scope are immutable snapshots after submission; revisions create new version records or a complete audited revision history.
- Replayed API calls must not create duplicate offers or multiple assignments. Use idempotency keys for critical create/accept/cancel/payment operations.
- When two customer selections race, only one transaction succeeds and the user receives a clear refresh/retry response.

### 8.3 Transition guards

- `provider_selected`: booking is still searching/offers state; chosen offer is active, not expired, provider is eligible/online (or in an allowed grace window), and no provider is assigned.
- `provider_arrived`: provider owns booking; previous state permits arrival; location/time evidence recorded where available.
- `in_progress`: provider owns booking; arrival requirement satisfied; trip PIN/OTP verified when enabled; required pickup evidence captured.
- `completed`: booking is in progress or a service-specific allowed state; required delivery/job proof exists; final totals are calculated from server-side snapshots.
- `cancelled`: validate actor, state, reason, time window, and fee policy. Do not cancel completed bookings through the ordinary cancellation endpoint.
- Operations overrides require permission, reason, audit entry, and customer/provider notification where appropriate.

### 8.4 Cancellation and no-show rules

Configure by service, market, time since confirmation, provider arrival, and responsible party:

- Free-cancellation grace period.
- Cancellation reason categories.
- Provider no-show / customer no-show reporting flow.
- Free waiting duration, extra waiting rate or negotiated additional charge if enabled.
- Cancellation fee amount or formula, who receives it, and dispute path.
- Evidence and cooldown/risk rules to prevent abuse.
- Provider appeal and support review.

Do not apply automatic cancellation penalties based only on a single GPS signal. Show fee policy before confirmation and before the customer commits to a fee-triggering cancellation.

---

## 9. Pricing and Negotiation Engine

### 9.1 Required pricing model

- Customer chooses a proposed fare.
- Provider may accept or counter-offer.
- Customer selects the offer they prefer.
- The selected fare is saved as an agreement snapshot.
- The platform may show a **recommended/reference price** as guidance only, using configured base fare, distance, duration, category, area, time, stops, and service attributes. Clearly explain that the guidance is not a guaranteed or forced quote.
- Do not use automatic demand-based fare multipliers that silently change the customer's offer. Any optional incentive should be explicitly shown and kept distinct from the agreed customer/provider fare.

### 9.2 Price composition

Support configurable components, only where applicable:

- Customer-proposed fare.
- Provider counter-offer.
- Accepted add-ons (helpers, extra stops, packing, special handling, extra waiting).
- Tolls/parking with configured actual-cost treatment and evidence policy.
- Customer service fee if used.
- Tax or regulatory charges where applicable.
- Coupon discount and discount funding source (platform/provider/partner).
- Tip.
- Payment processor fee if charged to user and legally permitted; otherwise absorb/handle internally.
- Refund, cancellation fee, adjustment, or dispute resolution entry.

Keep distinct amounts for customer payable, provider gross earning, platform commission/revenue, discount funding, tax payable, and net provider earnings. Never infer one from another using client totals.

### 9.3 Quote and offer controls

Admin-configurable controls:

- Minimum and maximum customer-proposed fare by service/category/area.
- Min/max provider counter-offer limits relative to initial fare or reference estimate.
- Currency precision and rounding rules.
- Offer maximum count per provider/request and maximum revisions.
- Offer response TTL and request search TTL.
- Dispatch radius progression and provider batch size.
- Spam/offer flooding limits.
- Reference price formula/version.
- Optional price tips/fairness warnings and localized copy.
- Price anomaly flags for manual/fraud review without silently rejecting legitimate negotiation.

### 9.4 No post-confirmation surprises

After a provider has been selected, fare modifications require a formal adjustment request visible to the other party and explicitly approved when the policy allows it. Record old/new values, reasons, evidence, actor, timestamps, approvals, and notifications. Disallow arbitrary unilateral adjustments.

---

## 10. Dispatch, Geospatial Search, and Realtime

### 10.1 Provider eligibility

A provider is eligible for a request only if all configured conditions are satisfied:

- Account approved and not suspended.
- Required documents and vehicle approvals valid.
- Online and fresh location available.
- Correct service category, vehicle capability, capacity, and service permissions.
- Provider within service area and dispatch distance/time limits.
- No conflicting active booking if provider policy allows only one active job.
- Meets intercity/scheduled/freight/other special requirements.
- Risk and workload restrictions satisfied.

### 10.2 Dispatch workflow

1. Store request and immutable service/policy snapshot.
2. Find eligible providers using geospatial search and filter by service/capability/area.
3. Notify provider batch or eligible set, according to configurable dispatch policy.
4. Accept incoming offers until request expires or is assigned.
5. Expand search area in controlled waves when configured; make no claim that every driver received the request.
6. Expire offers and requests through queues/scheduler, not only client timers.
7. Handle provider going offline, losing signal, or becoming ineligible by invalidating or revalidating their active offer.
8. When customer chooses a provider, atomically assign one provider and emit events to all relevant participants.

Avoid broadcasting exact customer phone/address to every nearby provider. Use staged disclosure according to booking status and privacy policy.

### 10.3 Live location and map behavior

- Save latitude/longitude with timestamp, accuracy, heading/speed where available, and source.
- Use throttled updates in background to balance battery, accuracy, and network usage.
- Use configurable update frequency for online idle vs approaching pickup vs active job.
- Reject clearly impossible jumps/invalid coordinate values and flag suspicious location data; do not silently fabricate a route.
- Keep a last-known location plus freshness indicator.
- For active trips, location visibility to customer ends according to retention policy after trip completion.
- Support route polyline/ETA/distance only through configured mapping adapter; handle quota, geocoding, directions, and connectivity errors gracefully.

### 10.4 Realtime infrastructure

Use the repository's supported Laravel broadcasting/realtime solution (for example, Laravel Reverb or a compatible hosted WebSocket provider), with private channels and authorization. Suggested event families:

- `booking.requested`
- `booking.search_expanding`
- `booking.offer_received`
- `booking.offer_updated`
- `booking.offer_expired`
- `booking.provider_selected`
- `booking.status_changed`
- `booking.location_updated`
- `booking.message_received`
- `booking.cancelled`
- `booking.completed`
- `payment.status_changed`
- `coupon.applied` / `coupon.rejected`
- `driver.verification_status_changed`
- `wallet.transaction_created`

WebSocket events are a delivery mechanism, not the source of truth. Persist important events and make clients refresh the booking if sequence numbers/timestamps indicate a gap.

---

## 11. Coupons, Promotions, Referrals, and Loyalty

### 11.1 Coupon requirements

Admin can create campaigns with:

- Code (optional for automatic campaign), localized title/description, terms, start/end dates and timezone.
- Discount type: fixed amount or percentage.
- Discount amount/percentage, max discount, minimum eligible fare, max uses per customer, global usage cap, and per-campaign budget.
- Applicable service families, vehicle categories, cities/zones, customer segments, new users/returning users, selected payment methods, or schedules.
- Funding source: platform, provider, partner, or split-funded.
- Whether coupon can be combined with another coupon, wallet credit, referral reward, or other promotion.
- Excluded fees/items (e.g., tip, tolls) and taxes treatment.
- Referral source, campaign attribution, and abuse controls.
- Status: draft, scheduled, active, paused, exhausted, expired, archived.

### 11.2 Coupon lifecycle

1. Customer enters code or eligible promotion is suggested.
2. Backend validates active dates, scope, user eligibility, route/service eligibility, minimum amount, use limit, budget, and stacking policy.
3. Backend reserves/locks usage at the correct point in checkout/request creation to prevent concurrent overuse. Define whether usage is reserved at booking or captured when the job completes.
4. UI displays validation result and clear discount breakdown.
5. On booking cancellation/expiration, release a reservation when policy permits.
6. On eligible completed/paid booking, commit usage and campaign spending.
7. Refund/dispute logic reverses or retains discount funding according to campaign policy.
8. Record every coupon attempt and redemption in an auditable ledger.

Never trust a discount amount sent by a client. Return the calculated amount and an explanation code from the API.

### 11.3 Referral and loyalty

Design data model/configuration for, and implement where feasible:

- Unique referral code/link.
- Referrer/referee eligibility and milestones.
- Referral fraud detection (same device/phone/payment method patterns, velocity, linked accounts) with manual review rather than automatic irreversible punishment on weak signals.
- Credits/bonus ledger with expiry and usage restrictions.
- Loyalty levels or job-count achievements.
- Provider referral campaigns.

---

## 12. Payments, Wallets, Commission, and Finance

### 12.1 Payment methods

Make the available methods market-configurable. Initial architecture should support:

- Cash paid to provider.
- Card payment through a payment gateway adapter.
- Mobile wallet/gateway payment through an adapter where available.
- Customer stored platform wallet/credits, if enabled.
- Mixed wallet + external method only if policy and gateway permit.

Do not hard-code a provider. Build a `PaymentGateway`/adapter interface with create, authorize/capture if supported, verify callback/webhook signature, query status, refund, and reconcile operations. Use sandbox/test mode until real credentials are supplied.

### 12.2 Payment state

Separate booking status from payment status. Suggested values:

`not_required`, `pending`, `requires_action`, `authorized`, `paid`, `failed`, `partially_refunded`, `refunded`, `cash_due`, `cash_collected`, `reconciliation_required`.

- Verify payment gateway webhook signatures and idempotency.
- Record provider event IDs to prevent duplicate posting.
- Do not mark card/wallet payment paid based on the Flutter callback alone.
- Handle late webhook, duplicate webhook, timeout, user closing the app, and payment success with delayed booking update.
- Use reconciliation reports for transactions with mismatched local/provider status.

### 12.3 Wallet and double-entry considerations

Use an immutable ledger. At minimum keep separate balances/accounts for customer wallet liability, provider payable, platform revenue/commission, promotional liabilities/expense, payment processor clearing, taxes payable, refunds, and cash-collected commission due where applicable. Prefer double-entry journal entries for financial correctness and auditability.

Rules:

- Never edit a posted transaction in place. Reverse with compensating entries.
- Manual wallet adjustment requires permission, reason, actor, timestamp, reference, and audit trail; high-value adjustment requires second approver.
- Prevent negative balance unless a defined account type/policy explicitly allows it.
- Wallet updates use transactions/row locks or equivalent concurrency controls.
- Payout requests reserve eligible balance to prevent duplicate payouts.
- Payout state: requested, under_review, approved, processing, paid, failed, rejected, cancelled, reversed.
- Store gateway/provider references and payout evidence.

### 12.4 Commission and settlement

Support rules by service, provider tier, region, campaign, and booking date. Keep the applicable rule/version snapshot on each booking. Display gross fare, commissions, platform fees, taxes, discounts, tip, provider net, and settlement status. Cash bookings must still accrue any platform amount due from providers.

---

## 13. Safety, Trust, Privacy, and Moderation

### 13.1 Minimum safety tools

- Phone verification for all active accounts.
- Driver and vehicle document verification before going online.
- Trip verification PIN/OTP for passenger rides.
- Live tracking and share-trip link with expiry.
- Emergency/safety button that opens a real incident flow and, if configured, local emergency contact instructions/call action. Do not claim automatic emergency dispatch unless integrated and tested.
- Trusted contacts.
- Customer/driver support access during active job.
- Incident report with category, narrative, timestamp, booking, attachments, and priority.
- Blocking/suspension workflows and document expiration checks.
- Contact privacy/phone relay when supported.
- Rating abuse/fraud moderation and appeal workflow.
- Restricted goods policy acknowledgement for delivery/freight.

### 13.2 Emergency action behavior

- Make it easy to reach during an active job but avoid accidental taps (confirmation or hold gesture may be appropriate, without excessive friction in a genuine emergency).
- On activation, capture booking ID, time, actor, available GPS, callback/contact details, and selected incident category.
- Notify the correct safety/operations team and send customer-configured trusted-contact data only according to consent and policy.
- Show user what action was actually taken. If the platform has no emergency-service integration, clearly state that emergency services must be contacted directly.
- Rate-limit abuse but allow urgent reports. Preserve evidence and audit access.

### 13.3 Privacy and retention

- Enforce role-based access and minimize exposure of identity documents, phone numbers, and live location.
- Signed URLs for private document/attachment downloads with short expiry.
- Encrypt sensitive secrets and use TLS in transit; apply storage encryption where supported.
- Define retention schedules for live locations, chat, documents, receipts, support tickets, and incident evidence. Legal/safety holds may override normal deletion, with restricted access and audit.
- Support account deactivation/deletion requests and explain data that must legally be retained.
- Provide consent, privacy policy, marketing opt-in separately from terms acceptance.
- Do not display exact home address or unnecessary personal data to unrelated providers.

---

## 14. Communication, Notifications, and Support

### 14.1 Notifications

Support push, in-app inbox, SMS/OTP, and email through adapters/configuration. WhatsApp or other channels are optional integration work, not a hard dependency.

Event examples:

- OTP sent/verified (do not log plaintext OTP).
- Driver application submitted/needs changes/approved/rejected.
- Booking request created and offer received.
- Counter-offer accepted/rejected/expired.
- Provider selected, arriving, arrived, job started/completed.
- Booking cancelled and cancellation fee applied.
- Payment pending/succeeded/failed/refunded.
- Coupon applied/rejected/expiring.
- Payout requested/approved/paid/failed.
- Document expiring/expired.
- Support reply, dispute update, safety case update.

Provide notification preferences, localized templates, delivery status, retry policy, and deduplication. Essential safety/booking notifications cannot be fully disabled where justified; explain this in settings.

### 14.2 In-app chat and calls

- Booking-scoped conversation between customer and selected provider. No general unsolicited messaging before a booking is confirmed unless a controlled clarification feature is enabled.
- Support text, timestamps, delivery/read state where useful, and image attachments only if approved and scanned.
- Block dangerous file types; impose size limits and retention policy.
- Mask phone numbers or use provider contact relay if available.
- For calls, use `tel:` as a fallback or integrate a relay/VoIP provider. Do not promise in-app calling until a real implementation exists.
- Preserve a limited audit trail for disputes, with restricted staff access and user-facing privacy policy.

### 14.3 Support tickets and disputes

Ticket categories: booking, fare/extra charge, payment/refund, coupon, driver/provider, customer conduct, lost item, damaged goods, delivery failure, safety/harassment, account, verification, payout, technical issue, other.

Required functionality:

- Create ticket linked to user and optionally booking/payment/payout.
- Priority, SLA, status, assigned group/agent, message thread, attachments, internal notes separated from user-visible notes.
- Status changes with audit events and notifications.
- Escalation path to finance/safety/operations.
- Customer/driver can reply, attach evidence, and see resolution.
- Refund or wallet adjustment is a separate authorized financial operation, not a free-form ticket status change.

---

## 15. Ratings, Reputation, and Abuse Prevention

- Customer rates provider after completion; provider may rate customer if product policy enables it.
- Prevent duplicate ratings and only accept ratings for eligible completed bookings.
- Optional structured questions, written feedback, report flag, and moderation state.
- Public rating is computed server-side using a documented formula; protect against manipulation and provide a minimum sample indicator.
- A low rating alone should not trigger instant permanent suspension. Use configurable thresholds, evidence, review, and appeal.
- Track cancellation, no-show, suspicious booking, payment disputes, offer spam, account farms, referral abuse, and location anomalies with explainable internal risk indicators.
- Use rate limits and device/session risk signals appropriately; do not expose internal fraud scores publicly.

---

## 16. Laravel Admin Panel — Required Modules

Admin UI should be responsive but optimized for desktop operations. Provide Arabic and English, RTL, searchable/filterable tables, pagination, saved filters where feasible, useful empty states, CSV export with authorization, bulk actions with confirmation, and audit records. Avoid gigantic screens that mix unrelated functions.

### 16.1 Operations dashboard

- Today/7-day/30-day KPIs: bookings by status/service, request-to-offer conversion, offer-to-selection conversion, completion rate, cancellation/no-show rate, active drivers, waiting requests, average offer response time, estimated/realized GMV, commission revenue, payment success/failure, open incidents, open tickets, pending driver verification, payouts pending.
- Time-series charts with timezone/date filters and drill-down links.
- Data freshness indicators; explain definitions for financial KPIs.
- Do not expose personally sensitive details on a broad dashboard.

### 16.2 Customers

- Search by ID, name, phone, status, registration date.
- Profile, bookings, payments, coupon use, tickets, ratings, restrictions, consent/policy history.
- Suspend/reactivate with reason and expiry if temporary.
- Trigger account verification/help message where allowed.
- Handle data export/deletion requests.
- Admin actions must be permissioned and audited.

### 16.3 Drivers/providers and verification

- Applications queue and workflow for approve, reject with reason, request more information, suspend, reactivate, and flag for review.
- Profile, service categories, vehicles, documents with expiry, verification history, jobs, cancellation metrics, rating, wallet/earnings (financial permissions required), incidents/tickets.
- Side-by-side document review, secure access, expiry reminders, configurable document rules per region/service.
- Provider/fleet/company profile support in the data model, even if fleet administration is phased.
- Never permit document edits/approval from a role that has only read permissions.

### 16.4 Vehicle and service catalog

- CRUD for service families, service categories, vehicle classes, capacity/cargo limits, localized labels, icons, active status, booking types, requirements, price bounds, commission profiles, payment methods, cancellation/waiting policy, offer timers, radius settings, document requirements, proof-of-delivery settings.
- Version configuration and preserve historical snapshots referenced by bookings.
- Preview customer/driver form schema before publishing.
- Safe validation prevents disabling the only mandatory platform service or breaking active bookings without warning.

### 16.5 Service areas and maps

- Countries, cities, service zones, polygon/coverage maps, service operating hours, category availability, allowed pickup/drop-off rules, dispatch radius policy.
- Geocoding/map provider configuration with secret handling.
- Geofence validation, restricted pickup locations, optional airport/venue rules.
- Preview a point/route to see which services are enabled; do not treat map drawing alone as a valid legal authorization.

### 16.6 Live booking operations and dispatch console

- Search/filter active bookings and view status timeline, route, offer list, assigned provider, last location freshness, payment status, and relevant support/incident flags.
- Map-based active job view with privacy-controlled detail.
- Authorized reassign/cancel/override workflows with mandatory reason and customer/provider communication.
- Manual intervention should not overwrite historical offer/price records.
- Tools for no-offer requests: retry dispatch, extend search, contact customer, or cancel/expire with reason.
- Show realtime connection health and last event time.

### 16.7 Fare/offer monitoring

- View submitted and counter-offers, offer response and conversion rates, fare distribution by service/zone/time.
- Configure reference price formula, bounds, offer TTL, maximum counter-offers, warning thresholds.
- Flag anomalies for review; do not directly edit accepted offers without audited corrective workflow.

### 16.8 Coupons and marketing campaigns

- Full coupon lifecycle, eligibility filters, budget/caps, funding source, scheduled activation, pause, preview, test eligibility, redemptions, failed attempts, campaign performance, abuse flags, export.
- Manage referral campaigns, promo banners, in-app announcements, and optional push campaigns with consent/preferences respected.
- Prevent editing a live coupon in a way that retroactively changes completed bookings. Version important changes.

### 16.9 Finance

- Booking/transaction/payment ledger, gateway events, commission rules, invoices/receipts where applicable, provider balances, cash-collected reconciliation, refunds, disputes, payout requests, payout batches, manual adjustment requests, settlement/reconciliation reports.
- Two-person approval for configured risk levels.
- Filters by service, city, provider, date, payment method, status, and reference ID.
- Export with permission and audit log.
- Financial totals must come from ledger/payment records, not unverified client or cached display totals.

### 16.10 Safety and incident center

- Incident queue, severity, assignment, linked booking/users/location snapshots, attachments, timeline, trusted-contact action record, staff notes, escalation, resolution, and restricted evidence export.
- Permission separation and audit every view/download of sensitive incident evidence.
- Support urgent queue filters and SLA timers.

### 16.11 Support and disputes

- Ticket inbox, assignment, tags, SLA, macros/response templates localized in Arabic/English, internal notes, attachments, escalation, linked financial operations, and reporting.
- Search by ticket/booking/user/reference.
- No financial adjustment from a support ticket without explicit finance permission and correct ledger service.

### 16.12 Reviews and moderation

- Review list, reports, abuse signals, moderation status, hide/restore reason, user appeals, and audit trail.
- Do not allow administrators to rewrite user reviews without a traceable moderation policy.

### 16.13 Content management / CMS

- Public homepage sections, service pages, service-specific FAQs, How It Works, Become a Driver/Provider, city/area pages, blogs/help articles, banners, app links, legal pages, footer, contact details, SEO title/description, Open Graph fields, canonical URLs, publish scheduling, localized Arabic/English content.
- Draft/review/publish roles, preview, safe HTML/Markdown sanitization, image upload rules.
- Arabic content is primary; English can be missing only when clearly configured to fall back safely.

### 16.14 Reports and analytics

- Booking funnel, supply/demand by zone/time/service, offered/accepted fares, provider acceptance/selection, trip completion, cancellation/no-show, coupon spend/ROI, provider earnings, platform commissions, payout ageing, payment reconciliation, support SLAs, safety incident trends, document expiry, user retention, referral outcomes.
- Clearly define numerator/denominator, timezone, and whether data is live or delayed.
- PII-sensitive exports require elevated permissions and audit.

### 16.15 Settings and integration health

- General settings, locales, currencies, phone defaults, service availability, terms/privacy version, fare configuration, commission profiles, cancellation policies, support contacts, notification templates, map provider status, push status, queue health, scheduler heartbeat, websocket health, payment webhook failures, storage health, app minimum version/force-update settings.
- Secrets should be stored through environment/secret management, not returned in API responses or rendered back to admin users.

### 16.16 Admin audit and access management

- Admin users, roles, permissions, MFA if supported, session management, allowed IP controls where appropriate, login activity, sensitive operation logs, before/after snapshots excluding secrets, export logs.
- Use soft deletes only where appropriate; finance/safety/audit records should not disappear through ordinary deletes.

---

## 17. Laravel Public Website — Required Pages and Behavior

### 17.1 Site-wide

- Responsive, fast, accessible, Arabic-first with correct RTL; language toggle to English.
- SEO-friendly server-rendered pages, localized metadata, sitemap, robots policy, canonical URLs, Open Graph metadata, structured data when valid.
- Cookie/consent banner only if needed for the configured analytics/tracking policy; do not add intrusive consent UX without reason.
- Header/footer, app download links, support contact, social links, accessible form controls, spam protection.
- Original brand identity with a recognizable transport/marketplace theme, not a copy of inDrive's brand assets.

### 17.2 Required public pages

- Home page explaining the “propose a fare and choose your provider” model.
- Passenger/customer app page.
- Driver/provider recruitment page and application entry.
- Individual service pages: city rides, tuk-tuk (where enabled), taxi/car, intercity, courier, furniture moving, freight.
- How it works — customer flow and provider flow.
- Safety and trust page describing real implemented safeguards accurately.
- Cities/service areas page managed from admin.
- FAQ/help center.
- Contact/support form.
- About/company page.
- Terms of service, privacy policy, cancellation/refund rules, restricted goods policy, driver/provider terms, coupon rules.
- App download links/QR codes.
- SEO-managed articles/news/blog optionally.

### 17.3 Website forms

- Contact form with server-side validation, throttling, spam prevention, privacy notice, ticket/reference confirmation.
- Driver/provider lead/application form that can create an onboarding lead; do not accept unvalidated sensitive documents through a public form with insecure storage.
- Optional waitlist/early access form.
- Optional web booking entry should use the same backend booking/domain services and be permissioned; do not build a second, inconsistent fare-negotiation engine for web.

---

## 18. Localization, Accessibility, and UI/UX Standards

### 18.1 Arabic first and RTL

- Arabic default for both apps, admin, and public website.
- Correct `Directionality.rtl`/equivalent at application-root and component level in Flutter; web uses correct `dir` and logical CSS properties (`margin-inline`, `padding-inline`, etc.) rather than brittle left/right overrides.
- Mirror directional navigation icons when semantically appropriate, but do not mirror logos, maps, media controls, or icons whose orientation is not directional.
- Test Arabic numerals, Latin plate numbers, phone numbers, mixed Arabic/English addresses, currency, and long translated labels.
- Keep Arabic fonts legible and consistent; package a reliable font or use platform fonts intentionally. Verify license if including a custom font.
- Layouts must work on small phones, large phones, tablets and desktop admin screens.

### 18.2 Interaction quality

Every asynchronous action needs loading, success, failure and retry states. Provide clear validation feedback close to fields; avoid clearing user-entered data after recoverable network errors. Forms should preserve input if the request fails. Prevent duplicate submit with UI and server idempotency.

Design common components: buttons, inputs, searchable selectors, address picker, map bottom sheet, fare selector, offer card, status chip, timeline, confirmation dialog, empty state, error state, skeleton, toast/snackbar, attachment uploader, money display, ratings, permission explanation.

### 18.3 Accessibility

- Semantic labels for buttons and map actions.
- Screen-reader support for service names, fares, statuses, and error messages.
- Sufficient contrast and scalable font sizing.
- Do not rely solely on color to communicate booking/payment state.
- Provide touch targets that work in real driving/courier contexts and avoid dangerous driver distraction. Driver UI must be simple, with large controls and limited typing while moving.

---

## 19. Data Model — Suggested Core Entities

Adapt to repository conventions; normalize where useful. Use migrations, foreign keys, indexes, soft deletes only where appropriate, and immutable snapshots for historical agreements.

### 19.1 Identity and users

- `users`: id, public UUID, name, phone E.164, phone_verified_at, email, password if needed for web admin only or provider-specific auth approach, profile photo, role/account type, preferred locale, status, timestamps.
- `customer_profiles`: user ID, preferences, trusted settings.
- `provider_profiles`: user ID, provider type, verification status, rating aggregates, completed job count, online state, capability settings.
- `admin_users` or shared user + guarded admin role, depending on current architecture.
- `roles`, `permissions`, pivot tables (or repository's permission package tables).
- `trusted_contacts`.
- `user_consents`, `policy_versions`, `account_deletion_requests`.
- `user_devices`/sessions with non-secret device metadata and push token management.

### 19.2 Vehicle, services, and location

- `vehicles`: provider ID, category, make/model/year, color, plate, capacity, status, approval state.
- `vehicle_photos`, `vehicle_documents`, `provider_documents` with type, encrypted/private storage path, expiry, review metadata.
- `service_families`, `service_categories`, `vehicle_categories`, provider-service pivots.
- `service_category_versions` or snapshot JSON/version table.
- `service_area_countries`, `cities`, `service_zones`/polygons, zone-service pivots, opening hours.
- `provider_locations`: provider ID, lat/lng, accuracy, bearing, speed, recorded_at, status; optimize retention and geospatial indexes.
- `saved_places`.

### 19.3 Booking and negotiation

- `bookings`: public UUID/code, customer ID, service category/version, booking type, status, pickup/destination coordinates and address snapshots, stops, schedule, service details, proposed fare, currency, selected offer/provider, payment status, coupon reference, timestamps, cancellation reason, route/estimation snapshot, configuration snapshot, idempotency reference.
- `booking_stops`: ordered locations and stop metadata.
- `booking_items` or JSON schema-validated `service_details` depending on category variability; retain important searchable attributes in columns.
- `booking_offers`: booking ID, provider ID, amount, currency, offer state, note/scope, expiry, timestamps.
- `booking_offer_versions` or immutable offer event/version table.
- `booking_status_events`: from/to, actor, reason, timestamp, metadata.
- `booking_assignments` or explicit assignment history.
- `booking_location_events`/trip tracking samples with retention controls.
- `booking_proofs`: type, storage reference, hash, actor, timestamp, OTP verification record, metadata.
- `booking_adjustment_requests`: amount/scope change, requester, reason, approval status, evidence.
- `booking_cancellations` with fee decision snapshot.

### 19.4 Communications, ratings, support, safety

- `conversations`, `conversation_participants`, `messages`, `message_attachments`, delivery/read receipts if supported.
- `notifications`, `notification_templates`, `notification_preferences`, `notification_deliveries`.
- `ratings`, `rating_tags`, `rating_reports`, `moderation_actions`.
- `support_tickets`, `ticket_messages`, `ticket_attachments`, `ticket_assignments`, `ticket_events`.
- `safety_incidents`, incident events, restricted attachments, escalation records, trusted-contact action records.

### 19.5 Promotions and referral

- `coupons`, `coupon_rules`/eligibility config, `coupon_redemptions`, `coupon_reservations`, `coupon_campaign_budgets`, `promotion_funding_entries`.
- `referral_codes`, `referral_relationships`, `referral_rewards`, `loyalty_accounts`/ledger if implemented.

### 19.6 Money and payout

- `payment_methods` (store tokens/references, never raw card details), `payments`, `payment_events`, `payment_refunds`, `gateway_webhook_events`.
- `wallet_accounts`, `wallet_transactions`/ledger entries, `journal_entries`, `journal_lines` if using double-entry.
- `commission_rules`, `commission_rule_versions`, booking commission snapshots.
- `provider_payout_methods`, `payout_requests`, `payout_batches`, `payout_events`.
- `cash_reconciliations`, `financial_adjustment_requests`.

### 19.7 Governance and configuration

- `platform_settings` or typed settings service; never use unvalidated arbitrary settings for security-critical controls.
- `admin_audit_logs` including action, actor, target, before/after safe snapshot, reason, IP/user agent where policy allows, timestamp.
- `outbox_events` or reliable event dispatch table for transactional notification/broadcast workflows when needed.
- `feature_flags`, `app_versions`, `system_health_events`, `idempotency_keys`.

### 19.8 Data integrity requirements

- Use UUID/public IDs in public APIs to avoid exposing sequential internals where appropriate.
- Store all money in a consistent exact representation (integer minor units or `DECIMAL`, not binary floating point). Document currency exponent rules.
- Save localized display snapshots and configuration version IDs for bookings; old receipts must not change when admin edits category names/commission.
- Index phone lookup securely, booking status, provider availability, offer expiry, transaction references, timestamps, geospatial queries, and admin search columns.
- Define retention/indexing strategy for location history and messages to avoid uncontrolled table growth.

---

## 20. REST API Requirements

Use versioned JSON APIs, consistent response shape, authorization policies, request validation, pagination, filtering, and resource serializers. Document endpoints in OpenAPI/Swagger or `docs/API.md`. Do not expose Eloquent models/hidden fields directly.

Suggested response envelope (adapt if repository already has a consistent convention):

```json
{
  "success": true,
  "message": "localized or stable message key",
  "data": {},
  "meta": {},
  "errors": {}
}
```

Use meaningful HTTP status codes. Validation failures return field-level errors. Rate-limited requests return retry metadata. Never return secrets, OTP values, full private document paths, gateway signing secrets, or internal fraud signals.

### 20.1 Authentication/profile endpoints

- `POST /api/v1/auth/otp/request`
- `POST /api/v1/auth/otp/verify`
- `POST /api/v1/auth/refresh` if the chosen auth method supports refresh tokens
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`
- `PATCH /api/v1/me`
- `POST /api/v1/me/photo`
- `GET /api/v1/me/consents`
- `POST /api/v1/me/account-deletion-requests`

### 20.2 Catalog/location endpoints

- `GET /api/v1/config/app`
- `GET /api/v1/locales`
- `GET /api/v1/service-families`
- `GET /api/v1/service-categories?lat=&lng=&booking_type=`
- `GET /api/v1/vehicle-categories?service_category_id=`
- `GET /api/v1/service-areas/resolve?lat=&lng=`
- `GET /api/v1/places/search?q=&lat=&lng=`
- `GET /api/v1/routes/estimate?origin=...&destination=...&service_category_id=...`

### 20.3 Customer booking/offer endpoints

- `POST /api/v1/bookings` — create validated booking/request and start dispatch.
- `GET /api/v1/bookings` — paginated customer history.
- `GET /api/v1/bookings/{booking}` — booking snapshot/timeline/current provider/allowed actions.
- `POST /api/v1/bookings/{booking}/offers/{offer}/accept` — select provider offer atomically.
- `POST /api/v1/bookings/{booking}/cancel` — cancel with reason; server calculates fee.
- `POST /api/v1/bookings/{booking}/adjustments` — request a permitted price/scope adjustment.
- `POST /api/v1/bookings/{booking}/share` — create time-limited trip-share token.
- `POST /api/v1/bookings/{booking}/rating`.
- `POST /api/v1/bookings/{booking}/reports`.
- `POST /api/v1/bookings/{booking}/repeat`.
- `POST /api/v1/bookings/{booking}/payment-intents`.
- `POST /api/v1/bookings/{booking}/tips`.

### 20.4 Driver endpoints

- `POST /api/v1/provider/applications`
- `GET /api/v1/provider/application`
- `POST /api/v1/provider/documents`
- `GET /api/v1/provider/documents`
- `POST /api/v1/provider/vehicles`
- `PATCH /api/v1/provider/vehicles/{vehicle}`
- `POST /api/v1/provider/vehicles/{vehicle}/submit-review`
- `POST /api/v1/provider/availability/online`
- `POST /api/v1/provider/availability/offline`
- `POST /api/v1/provider/location` — authenticated, validated and rate-limited.
- `GET /api/v1/provider/requests/available`
- `POST /api/v1/provider/bookings/{booking}/offers/accept-customer-fare`
- `POST /api/v1/provider/bookings/{booking}/offers` — create a counter-offer.
- `POST /api/v1/provider/offers/{offer}/withdraw`
- `POST /api/v1/provider/bookings/{booking}/arrived`
- `POST /api/v1/provider/bookings/{booking}/verify-start-pin`
- `POST /api/v1/provider/bookings/{booking}/start`
- `POST /api/v1/provider/bookings/{booking}/proofs`
- `POST /api/v1/provider/bookings/{booking}/delivery-attempts`
- `POST /api/v1/provider/bookings/{booking}/complete`
- `GET /api/v1/provider/earnings/summary`
- `GET /api/v1/provider/earnings/transactions`
- `GET /api/v1/provider/payouts`
- `POST /api/v1/provider/payouts`

### 20.5 Coupons, payments, wallet, chat, support

- `POST /api/v1/coupons/validate`
- `GET /api/v1/me/coupons`
- `GET /api/v1/me/wallet`
- `GET /api/v1/me/wallet/transactions`
- `GET /api/v1/me/payment-methods`
- `POST /api/v1/me/payment-methods/setup-intent`
- `DELETE /api/v1/me/payment-methods/{payment_method}`
- `POST /api/v1/payment-gateways/{gateway}/webhook` — public network endpoint with signature verification and provider-specific validation.
- `GET /api/v1/bookings/{booking}/messages`
- `POST /api/v1/bookings/{booking}/messages`
- `GET /api/v1/support/tickets`
- `POST /api/v1/support/tickets`
- `GET /api/v1/support/tickets/{ticket}`
- `POST /api/v1/support/tickets/{ticket}/messages`
- `POST /api/v1/safety/incidents`

### 20.6 Admin endpoints

Use `/api/v1/admin/...` only if admin functionality is API-driven; server-rendered Laravel routes are equally valid. Every operation must use policy/gate authorization.

Areas: dashboard, customers, drivers, verification, vehicles, service catalog, service areas, bookings, live dispatch, offers, coupons, finance, commissions, wallets, payouts, refunds, incidents, tickets, reviews, CMS, reports, settings, users/roles/permissions, audit logs, integration health.

### 20.7 API security and reliability

- Use Laravel Sanctum or the existing secure token strategy for mobile clients; enforce token ability/scopes where useful.
- OTP request/verification rate limits by account/phone/IP/device signals; avoid revealing whether an arbitrary phone number has an account.
- Request idempotency on booking creation, offer selection, provider job transitions, payment/refund/payout operations.
- Server-side authorization on every object to prevent insecure direct object reference (IDOR).
- Validate ownership: customer can access own bookings; driver can access only assigned jobs and eligible request summaries; support sees only assigned/authorized tickets.
- Use database transactions for booking selection, money movement, coupon reservation, and state transitions.
- Keep external webhooks separate from user auth but strongly verify signature, timestamp/replay window and idempotency.
- API errors should be useful but not leak SQL, stack traces, file paths, secret values, or private records.

---

## 21. Realtime Events and Push Delivery

Define a central domain event for each meaningful action. Persist durable events in the transaction/outbox where reliable delivery matters. Broadcast only to authorized participants/channels.

Example private channels:

- `private-customer.{customerId}`
- `private-provider.{providerId}`
- `private-booking.{bookingUuid}`
- `private-admin.operations` with role/permission filtering where supported

Authorize every subscription server-side. Do not rely on guessed channel names. Ensure customer can subscribe only to their own bookings; provider can subscribe only to their assigned or eligible request streams; support/admin channels are permissioned.

Push notifications must contain minimal sensitive content on lock screens. Clicking a notification should deep-link to the relevant booking/ticket and gracefully handle expiry, logout, and invalid state.

---

## 22. External Integrations — Use Adapters

Implement interfaces so providers can be changed without rewriting business logic. List the selected provider and required secrets in `docs/INTEGRATIONS.md`.

- Maps/geocoding/directions/ETA/geospatial maps.
- Push notifications (Firebase Cloud Messaging and APNs through an appropriate SDK/service).
- SMS/OTP provider; safe development OTP provider restricted to local/test environments.
- Payment gateway for cards and/or local wallets, selected based on launch-market availability and merchant eligibility.
- Email delivery.
- Object/file storage for documents and proof photos.
- Realtime/broadcasting (e.g., Laravel Reverb or compatible provider).
- Optional phone relay/in-app voice, analytics, crash reporting, and remote config.

If a provider credential is unavailable, keep the adapter contract, sandbox/local implementation, health status, and clear setup documentation. Never fake a production success response.

---

## 23. Non-Functional Requirements

### 23.1 Performance and scalability

- Paginate lists server-side. Avoid fetching all history/offers/providers at once.
- Index status, expiry, owner, and date fields; use geospatial indexing or an appropriate spatial/geohash strategy.
- Cache low-risk catalog/config data with invalidation; never rely on stale cache for authorization or balances.
- Use queues for SMS, email, push, image processing, analytics aggregation, expiry jobs, and non-critical events.
- Keep dispatch and offer selection responsive under expected city launch traffic; benchmark the actual target environment rather than inventing claims.
- Provide loading skeletons and incremental map updates in Flutter.

### 23.2 Reliability

- Critical transitions and financial operations are transactional and idempotent.
- Scheduled jobs must be safe to rerun and monitorable.
- Queue failure/retry and dead-letter/manual retry procedures are documented.
- Add health checks for database, cache, queue, scheduler, storage, map/payment/notification adapters when feasible.
- Provide automated database backup and restore guidance; backups must be protected and restoration tested.

### 23.3 Security

- Follow current Laravel security practices, secure headers, CSRF for session-authenticated website/admin forms, authorization policies, validation, escaping, rate limiting, and least privilege.
- Store password hashes using framework algorithms if passwords exist; mobile primary auth should not rely on a weak static password pattern.
- Secure mobile token storage and protect sensitive screens/logging from accidental leakage where appropriate.
- File upload validation by MIME, extension, size, and image decoding; malware scanning can be adapter-based.
- No secrets in logs, crash reports, analytics, screenshots, client bundles, or public API responses.
- Configure CORS strictly and apply production TLS.
- Do not log precise live location longer than necessary in generic application logs.
- Conduct dependency/security checks as supported by the repository toolchain.

### 23.4 Observability

- Structured logs with request/correlation ID.
- Domain audit events for sensitive operations.
- Metrics for booking funnel, job latency, websocket delivery, dispatch, payment callbacks, queue depth/failure, payout status and app crashes where integrated.
- Never include OTP, card secrets, credentials or unnecessary PII in logs.
- Error tracking integration is configurable; safe local logging remains available.

### 23.5 Maintainability

- Follow current repository conventions. Prefer small domain services/actions, DTOs/value objects where they improve clarity, policies, validated request objects, API resources, and repository abstractions only where useful.
- Avoid giant controllers, fat widgets, duplicated negotiation logic, untyped service forms, and magic status strings.
- Centralize booking-state transitions and money calculations.
- Include unit, feature/API, integration, widget/component, and end-to-end tests at appropriate layers.

---

## 24. Recommended Laravel Structure

Adapt to current repo and framework conventions rather than forcing a new structure blindly. A possible modular layout:

```text
app/
  Domain/
    Identity/
    Customers/
    Providers/
    Vehicles/
    ServiceCatalog/
    ServiceAreas/
    Dispatch/
    Bookings/
    Offers/
    Pricing/
    Payments/
    Coupons/
    Wallets/
    Payouts/
    Notifications/
    Messaging/
    Ratings/
    Safety/
    Support/
    Cms/
    Analytics/
  Http/
    Controllers/Api/V1/
    Controllers/Admin/
    Requests/
    Resources/
    Middleware/
  Policies/
  Jobs/
  Events/
  Listeners/
  Notifications/
  Console/Commands/
  Support/
resources/
  views/website/
  views/admin/
  lang/ar/
  lang/en/
database/
  migrations/
  seeders/
tests/
  Unit/
  Feature/
docs/
```

Organize business use cases as small actions/services such as `CreateBooking`, `FindEligibleProviders`, `SubmitProviderOffer`, `AcceptProviderOffer`, `TransitionBookingStatus`, `CalculatePriceBreakdown`, `ReserveCoupon`, `CreatePaymentIntent`, `CompleteBooking`, `PostLedgerEntries`, and `RequestPayout`. Use dependency injection. Unit tests should be able to test deterministic business logic without real payment/map/SMS calls.

---

## 25. Recommended Flutter Structure

Do not blindly replace an existing architecture. Keep customer and driver apps independent deployable applications but reuse a small shared package for stable common items if a monorepo is practical (localized money, API client conventions, design tokens, shared models, map/location abstractions). Avoid coupling the two app navigation systems.

Possible layout for each app:

```text
lib/
  app/                 # router, theme, localization, bootstrap
  core/
    api/
    auth/
    config/
    errors/
    localization/
    permissions/
    realtime/
    storage/
    widgets/
  features/
    onboarding/
    home/
    service_catalog/
    booking/
    offer_negotiation/
    live_tracking/
    payments/
    coupons/
    wallet/
    notifications/
    messaging/
    ratings/
    support/
    safety/
    profile/
```

Use the state-management/router solution already adopted if sound; otherwise select a well-supported approach, document it, and use it consistently. Repository/data-source boundaries should make API behavior testable. Avoid huge screen widgets with mixed network, business, and map logic.

Flutter requirements:

- Material design customized to original brand tokens.
- RTL and localization initialized before first meaningful frame.
- Secure token storage and safe refresh/logout logic.
- API timeout, offline, retry, empty, loading, expired-session and permission-denied states.
- Push/deep links for booking and driver offers.
- Location permission lifecycle and iOS/Android foreground/background restrictions handled according to supported use cases.
- Clear platform permissions rationale and app store privacy disclosures.
- App version/minimum version checks, graceful maintenance state.
- Integration tests for core lifecycle, not only static widget tests.

---

## 26. Website/Admin Engineering Requirements

- Public website routes must have correct localized slugs and metadata.
- Admin routes must require authenticated admin sessions and permission checks.
- Separate admin and public layouts, navigation, middleware and session policy.
- CSRF protection for session-authenticated form submissions.
- Search/list pages use server-side pagination, filters, sorting allowlists, empty states and useful validation.
- Destructive and financial actions require confirmation and appropriate reason/approval workflow.
- Forms show validation messages and preserve input after errors.
- Uploads use private/public storage rules intentionally.
- Admin should not expose internal exception details to operators; logs contain correlation IDs.
- Audit log includes important create/update/delete/publish/approve/reject/override/export/view-sensitive-record actions where appropriate.

---

## 27. Testing Strategy and Required Scenarios

### 27.1 Backend unit tests

- Fare breakdown, rounding, currency rules and commission calculations.
- Coupon eligibility and funding-source allocation.
- Booking state transition guards.
- Offer validation, expiry, and counter-offer limits.
- Provider eligibility/service/vehicle/capacity validation.
- Cancellation fees and no-show policy.
- Ledger double-entry balancing/reversal rules.
- Provider payout balance/reservation calculations.
- Localized formatting/helpers where testable.

### 27.2 Backend feature/integration tests

- OTP request throttling and verification.
- Unauthenticated/unauthorized requests rejected.
- Customer A cannot see/cancel Customer B's booking.
- Provider cannot access unrelated private job data.
- Driver cannot go online before required approvals/documents.
- Customer creates request; eligible provider receives it; provider accepts or counter-offers; customer selects one; another competing acceptance fails safely.
- Offer expires even if mobile client remains open.
- Customer route/service change invalidates or revalidates offers as defined.
- Trip PIN required before start.
- Completion requires correct proof for courier/freight services.
- Duplicate booking/accept/completion/webhook calls remain idempotent.
- Invalid coupon cannot discount price; concurrent coupon use respects caps.
- Payment webhook signature and replay rejection; duplicate events do not double-credit.
- Refund/payout/ledger entries balance and are auditable.
- User cannot read private files without authorization/valid signed URL.
- Admin role restrictions and audit records work.

### 27.3 Flutter tests

Customer app:

- Arabic is selected on first install.
- All important screens render RTL and English LTR.
- OTP flow errors/resend limits.
- Pick route, complete service-specific fields, enter fare, apply coupon, submit request.
- Receive offers, compare, select provider; expired offer cannot be selected.
- Tracking, stale location, cancellation confirmation and payment errors.
- Customer with no booking, no offers, no network, denied GPS and empty history sees useful states.

Driver app:

- Incomplete/rejected/pending provider cannot go online.
- Offer card correctly reflects category, fare, scope and timer.
- Accept/counter-offer/skip states, duplicate taps and expired offers.
- Arrival/start PIN/completion sequence.
- Parcel recipient unavailable, proof upload retry, no-network and GPS errors.
- Earnings/payout states and secure logout.

### 27.4 Website/admin tests

- Public pages localized, metadata present and forms validated/throttled.
- Admin auth required, permissions enforced on routes and actions.
- Coupon create/edit/pause and booking config workflows.
- Verification approval/rejection requires reason and creates audit event.
- Sensitive finance/incident screens obey role permissions.
- Exports are permissioned and audited.

### 27.5 E2E critical path

At least one automated happy-path test should cover:

1. Customer registers with test OTP.
2. Approved provider is online with a valid vehicle.
3. Customer creates a tuk-tuk/car ride or selected transport booking with a proposed fare.
4. Provider receives request and counter-offers.
5. Customer compares and selects provider.
6. Provider marks arrived and verifies customer PIN.
7. Provider starts and completes job.
8. Payment/ledger update is processed in the configured test mode.
9. Customer rates provider and sees trip in history.
10. Admin can view the booking timeline and its audit history.

Also include one parcel/furniture/freight flow to ensure the product is not only a taxi clone.

---

## 28. Seed Data and Development Demo Mode

Provide safe seeded records for local development/testing:

- Admin roles and a documented local admin account mechanism (never a production default credential).
- Sample city/service area.
- Enabled categories: tuk-tuk, taxi/economy car, standard car, courier motorcycle, pickup truck, furniture moving truck, freight truck.
- Sample customers and providers clearly marked as test fixtures.
- Approved/unapproved provider states and valid/expired document examples.
- Sample coupons for percentage, fixed discount, new customer, service-restricted and expired cases.
- Sample bookings in key lifecycle states.

Development OTP bypass/test code may exist only under an explicit local/testing environment guard. In production, the bypass must be impossible to activate through client parameters or public configuration.

---

## 29. Deployment, Environments, and Operations

Support separate local, staging, and production configuration.

Document:

- Supported PHP/Laravel/Flutter/Dart versions actually chosen after repository inspection.
- Required extensions, database, cache, queue backend, object storage, Node toolchain if used by Laravel frontend assets, and Android/iOS toolchains.
- `.env.example` with descriptions of every required variable.
- Database setup/migrations/seeders.
- Queue worker configuration and process manager setup.
- Scheduler/cron setup and health heartbeat.
- Websocket/realtime configuration.
- File storage permissions and private-document access.
- TLS/domain/reverse proxy settings.
- Mobile API base URL configuration per environment.
- Firebase/APNs setup and app identifiers.
- Android release signing and iOS signing/provisioning done outside source control.
- CI steps for PHP tests/formatting, Flutter format/analyze/tests/build, and secret scanning where available.
- Backup/restore process, retention, encryption, and restore test.
- Rollback procedure and migration compatibility guidance.
- Production readiness checklist and external credential checklist.

Never commit `.env`, `.p12`, keystores, provisioning profiles, private keys, Firebase service-account JSON, unredacted backups, or user documents.

---

## 30. Phased Implementation Plan and Priorities

Claude should inspect what is already implemented and adapt these phases. Do not re-implement functioning modules. A milestone is complete only after relevant tests pass and status/docs are updated.

### Phase 0 — Repository assessment and product foundation

- Repository audit, architecture diagram, dependency/version inventory, test/build baseline.
- Implementation plan, decisions log, API conventions, brand/design tokens, locale setup.
- Define status enums/state machine and money representation.
- Establish CI/check scripts where feasible.

### Phase 1 — Core platform foundation

- Auth/OTP abstraction, customer/provider/admin identities, policies/permissions.
- Arabic-first localization and RTL in both Flutter apps, website and admin.
- Service catalog and data-driven vehicle/service configuration.
- Service areas and map adapter abstraction.
- Admin user/role management, system settings, audit log foundation.

### Phase 2 — Core fare-negotiated passenger ride MVP

- Customer route selection, service selection, proposed fare, request creation.
- Provider onboarding and approved driver/vehicle fixture flow.
- Online/offline, location updates, dispatch and incoming request cards.
- Provider accept/counter-offer/skip.
- Customer offer comparison and atomic selection.
- Provider pickup/arrival/PIN/start/complete.
- Live location/realtime updates, basic push, booking history, ratings.
- Core tests from customer request through completed ride.

### Phase 3 — Coupons, payments, earnings, and support

- Coupon rules/reservations/redemptions.
- Payment gateway abstraction, cash flow, sandbox card/wallet adapter as configured.
- Commission and ledger, provider earnings, payout request/review foundation.
- Cancellation/no-show, receipts, support tickets, notifications.

### Phase 4 — Courier, furniture moving, and freight

- Category-specific forms and backend validation.
- Delivery contacts and tracking, recipient OTP, proof of delivery/attempt failure.
- Furniture moving scope, helper/load/unload options and photo attachments.
- Freight capacity, loading requirements, multi-stop/cargo records, proof/receipt.
- Offers show structured scope and agreed inclusions/exclusions.
- Integration tests for at least parcel and furniture/freight workflows.

### Phase 5 — Full operations and public website

- Admin live operations console, verification queue, catalog editor, service zones, coupon manager, finance/payout/reconciliation, support/dispute, incidents, reports, CMS.
- Public SEO website pages, service landing pages, driver recruitment, contact forms, policies and localization.
- Export permissions, audit coverage and integration health.

### Phase 6 — Production hardening and release

- Security and IDOR review, race/idempotency review, payment webhook review.
- Performance/load test baseline and query tuning.
- Location battery/privacy test, no-network recovery tests.
- Arabic/English layout review across screen sizes.
- CI builds, store configuration checklist, staging UAT, backups/restore, monitoring and release notes.

### P0 / P1 / P2 priority policy

- **P0 release blockers:** auth/security, fare negotiation, atomic provider selection, booking state machine, core ride lifecycle, provider eligibility, Arabic/RTL, audit on privileged operations, essential tracking, cancellation flow, data integrity, tests and buildability.
- **P1 launch-grade modules:** coupons, payment adapter/cash reconciliation, earnings/commission, courier proof, support/disputes, CMS public website, documents/expiry alerts, operational dashboards.
- **P2 expansion:** loyalty/referrals, fleets, advanced incentives, hourly bookings, merchant/grocery supply, in-app VoIP/phone relay, richer analytics, advanced fraud tooling.

Do not use priority labels as an excuse to leave mandatory stated P0 flows half-built. If a P1/P2 feature is not implemented, label it clearly in the status report and do not surface a fake working UI for it.

---

## 31. Acceptance Criteria — Product-Level

The project is accepted only when all applicable criteria below are met or explicitly listed as unimplemented with a reason and next step.

### Customer lifecycle

- Customer can register/login using phone OTP in configured environments.
- Arabic is the initial/default language and RTL works on core screens.
- Customer can select a service and vehicle category available in their service area.
- Customer can enter pickup/destination and required service details.
- Customer can propose a fare and apply a valid coupon with a correct breakdown.
- Customer can submit a booking and receive live offers.
- Customer can compare provider profiles, vehicles, ratings, completed jobs, ETA and fare.
- Customer can select one offer; competing selections cannot assign two providers.
- Customer can view live provider location and status with freshness indication.
- Customer can cancel according to policy, see any fee before final confirmation, and contact support.
- Customer can view completed trip receipt and history, rate provider, and report an issue.

### Provider lifecycle

- Provider can apply, create vehicle/service profiles, upload documents, and see verification states.
- Unapproved/ineligible provider cannot go online or receive private full booking information.
- Eligible provider can go online/offline and receive compatible requests.
- Provider can accept proposed fare, counter-offer, or skip.
- Provider can see whether a counter-offer is pending, selected, rejected, expired, or invalidated.
- Selected provider can navigate to pickup, mark arrived, verify PIN where required, start, perform required milestones, and complete.
- Provider can view earnings/transactions/payout state without altering financial records.

### Admin lifecycle

- Admin permissions govern every privileged screen/API/action.
- Admin can approve/reject providers and vehicles with reason and history.
- Admin can configure service/vehicle categories, fare limits, dispatch timers, zones, commission, cancellation and wait policies.
- Admin can find bookings, see status/offer timeline, and perform controlled overrides with audit.
- Admin can create coupon campaigns and inspect usage/budget.
- Admin can manage tickets/incidents and access sensitive data only with appropriate permissions.
- Finance can reconcile payments, inspect commission/payout ledgers, and perform controlled refunds/adjustments.
- CMS editor can publish localized website content without code changes.

### Delivery/furniture/freight lifecycle

- Request forms capture the service-specific attributes required by the chosen category.
- Provider offers clearly specify relevant vehicle/capacity/helpers/inclusions and customer accepts the scope.
- Pickup/transport/delivery milestones are tracked and displayed.
- Required recipient verification/proof is enforced before completing applicable jobs.
- Failed delivery or scope problem can be reported without falsely marking the booking complete.

### Technical quality

- No known critical authorization bypass or payment/ledger integrity defect remains.
- Critical server-side state transitions and money operations are idempotent and tested.
- No fake success on external integrations.
- Core PHP/Laravel tests and Flutter analysis/tests run successfully in the actual environment, or precise blockers are documented.
- `.env.example`, setup, deployment, queue/scheduler, API, and integration docs are present.
- No real secrets are committed.

---

## 32. Important Product Decisions to Keep Configurable

Record decisions in `docs/DECISIONS.md` and implement these as settings where appropriate:

1. Whether passenger rides, courier, moving, and freight all launch on day one or are enabled in stages.
2. Whether customers can schedule rides and how far in advance.
3. Offer expiry and dispatch radius expansion rules.
4. Whether drivers may hold multiple concurrent bookings (default: no, unless explicitly built and tested).
5. Exact cancellation fees, free-wait duration, no-show rules and appeal policy.
6. Commission/fixed-fee model, settlement cycle, and payout provider/process.
7. Cash support by service type and how cash-collected commissions are settled.
8. Supported local payment gateway(s) and eligibility/merchant account requirements.
9. Maps provider and SMS/OTP provider.
10. Driver document requirements by region/service and review process.
11. Whether phone relay, in-app calls, or direct calls are enabled.
12. Whether provider/customer can rate each other.
13. Data retention durations and access for safety evidence.
14. Legal entity, taxation, transport licensing, insurance, and restricted-goods policies.
15. Public booking on the website vs website limited to information and recruitment.

For unspecified decisions, choose safe configurable defaults and do not block implementation.

---

## 33. Final Execution Prompt for Claude Opus 5.5

Use the following prompt together with this SRS:

> You must implement the multi-service, inDrive-inspired transport and delivery platform described in this SRS in the current repository. First inspect the complete codebase and report the real current state in `docs/IMPLEMENTATION_PLAN.md`; do not assume the repository is empty or that earlier claims about it are true. Preserve existing work and adapt architecture to what is already present.
>
> The mandatory core behavior is fare negotiation: the customer proposes a price, eligible providers can accept or counter-offer, and the customer compares/selects a provider. Build this as a transactional server-side lifecycle, not a static UI. The same foundation must support tuk-tuk, taxi/car, motorcycle courier, furniture moving/pickup truck, and freight vehicle categories through configurable service definitions and service-specific form schemas.
>
> Required client/platform deliverables are a Flutter customer app, a Flutter driver/provider app, a Laravel admin operations panel, a Laravel public website, and a Laravel REST backend. Arabic is the default language and all principal experiences must support correct RTL; English must work in LTR. Egypt/EGP are initial configurable defaults, not hard-coded assumptions.
>
> Complete work in testable vertical milestones. For each feature implement the database schema/migrations, server-side domain actions and policies, API/resource validation, client/server integration, Arabic and English UI, loading/error/empty states, automated tests, and docs. Use the existing dependency conventions where sound. Do not make giant untested rewrites, do not add dead buttons, and do not claim integrations work if credentials are absent. Use safe adapters and documented local/test implementations where needed.
>
> Keep all money calculations, fare/offer acceptance, cancellation fees, coupon eligibility, commission, ledger updates, payment verification, provider eligibility, OTP enforcement and trip state transitions on the server. Protect against IDOR, duplicate actions, expiry races, double assignment, duplicate webhooks, negative or inconsistent balances, unauthorized document access, and private-channel subscription abuse. Use transactions, locks/idempotency and immutable audit/ledger entries as appropriate.
>
> Do not ask me to design each screen or make routine product decisions. Follow this SRS, inspect repository facts, choose sensible configurable defaults and record assumptions in `docs/DECISIONS.md`. Implement the highest-priority complete vertical slice first, then continue in the phase order. Update `docs/IMPLEMENTATION_STATUS.md` after every milestone with implemented items, files changed, tests actually run and passed/failed, remaining blockers, and precise next tasks.
>
> Before finishing, run every relevant formatter, static-analysis command, backend test suite, Flutter analyze/test/build command available in the environment. Inspect the git diff for accidental deletion, secrets, debug bypasses, broken routes, untranslated text, unprotected endpoints, and placeholder UI. Fix issues you can reproduce. End with a truthful release report: completed, partially completed, not implemented, exact verification commands/results, environment variables/external credentials needed, and deployment steps. Do not claim any command passed unless its output confirms success.

---

## 34. Glossary

- **Customer-proposed fare:** the initial price entered by a customer when creating a booking request.
- **Counter-offer:** a price proposed by a provider that differs from the customer's initial fare.
- **Offer:** a provider's response and price/scope proposal for a booking.
- **Selected provider:** the only provider assigned to a confirmed booking.
- **Reference estimate:** optional route/service price guidance; it is not the agreed fare.
- **Agreed fare:** the immutable accepted price snapshot plus explicitly accepted adjustments.
- **Service family:** broad class such as passenger ride, courier, moving, or freight.
- **Vehicle category:** a configured vehicle/vehicle-capability type such as tuk-tuk, taxi, pickup, or heavy truck.
- **Provider:** a driver, courier, moving operator, or other eligible service supplier using the provider app.
- **Proof of delivery:** evidence such as recipient OTP, photo, signature, or other configured record that supports completion.
- **Ledger:** immutable financial journal/transactions used to track funds, earnings, commission, discounts, refunds, and payouts.
- **RTL/LTR:** right-to-left layout for Arabic and left-to-right layout for English.

---

**End of specification.**
