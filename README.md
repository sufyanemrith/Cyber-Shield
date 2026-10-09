# iPHARMATIC

**Care within reach — an online pharmacy designed around convenience and prescription safety.**

![PHP](https://img.shields.io/badge/Backend-PHP-777BB4?style=flat-square)
![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-00758F?style=flat-square)
![Status](https://img.shields.io/badge/Status-In%20development-597c62?style=flat-square)

iPHARMATIC is the pharmacy web application developed in the **Cyber-Shield** repository. It is designed to help a community pharmacy in Mauritius offer its services online while keeping customer, pharmacist, administrator and delivery responsibilities clearly defined.

[About](#about-ipharmatic) · [Features](#what-the-website-is-designed-to-do) · [Customer journey](#customer-journey) · [User roles](#user-roles) · [Security](#security-and-privacy) · [Local setup](#run-locally)

## About iPHARMATIC

Visiting a pharmacy or arranging purchases by telephone can make routine medicine orders inconvenient. Customers may also struggle to keep track of previous purchases, prescription reviews and delivery updates when these activities are handled separately.

iPHARMATIC brings these interactions into one proposed web application. Customers can explore the product catalogue before creating an account, while registered customers are intended to manage purchases and prescriptions through their own account. Pharmacy staff use separate workflows to review prescriptions, monitor stock and support customer enquiries.

A central part of the design is **pharmacist review of prescription medicines**. Uploading a document does not automatically approve an order: the intended workflow requires a pharmacist to review it, record a decision and allow the relevant order to proceed only when its requirements are satisfied.

## What the website is designed to do

The following describes the intended pharmacy service. The complete shopping, prescription and delivery workflows are still under development.

### Browse and find medicines

Visitors can explore over-the-counter and prescription product listings, search the catalogue, view product details and read customer reviews. Browsing is available without an account; adding items to a cart or making a purchase requires the customer to sign in.

### Manage a customer account

Customers register and log in using their email address. Their account is intended to provide access to their orders, purchase history, prescription submissions and delivery updates. Each customer should see only the records that belong to them.

### Build a cart and place an order

Registered customers can select products, adjust quantities and review their cart before checkout. The ordering workflow is designed to check product availability and whether a prescription is required, then maintain an order record that the customer can follow.

### Submit prescriptions for review

For products that require a prescription, customers upload a document for pharmacist review. The pharmacist can approve or reject the submission and record a reason. The system is intended to communicate the result to the customer and prevent restricted orders from progressing without the required approval.

### Make payments and request refunds

The checkout design includes payment-method selection and a payment status linked to the order. Refund handling is also part of the intended customer service. Payment processing is a planned integration; this reference does not collect or store payment-card details.

### Track deliveries

Customers are intended to follow order progress from processing and packing through dispatch and delivery. Delivery riders receive the information needed for their assigned work, update delivery progress, record delivery confirmation and mark unsuccessful deliveries when necessary.

### Read and share reviews

Customers can leave product reviews, and visitors can use those reviews when exploring the catalogue. Administrators are intended to monitor inappropriate review content as part of managing the website.

### Support pharmacy operations

Pharmacists monitor medicine inventory, review prescriptions and respond to customer queries. Administrators manage products, staff accounts, reports and audit records. Keeping these responsibilities separate helps the website reflect how the pharmacy operates.

## Customer journey

1. **Explore:** browse medicines, read product information and check reviews.
2. **Sign in:** create an account or log in before adding products to a cart.
3. **Select:** choose products and quantities, then review the order.
4. **Verify:** submit a prescription when required and receive the pharmacist's decision. Rejected submissions must not authorize a restricted order.
5. **Checkout:** proceed when stock and prescription requirements are satisfied, select the supported payment method and receive order confirmation.
6. **Track:** follow the order's progress and delivery updates through the customer account.
7. **Review:** view purchase history and leave a product review.

This is the intended end-to-end workflow; the current authentication reference does not yet implement every step.

## User roles

| Role | Main purpose | Access boundary |
|---|---|---|
| **Guest** | Explore products and reviews; register or log in | Cannot perform customer cart, purchase or prescription actions without signing in |
| **Customer** | Manage purchases, prescriptions, payments, reviews and delivery tracking | Access only their own private records |
| **Pharmacist** | Review prescriptions, monitor inventory and advise customers | Pharmacy permissions; no unrestricted administrator actions |
| **Administrator** | Manage products, staff roles, reports and audit records | Management permissions; prescription contents remain subject to the project's role restrictions |
| **Delivery rider** | Carry out assigned deliveries and update their status | Access only the delivery information and actions needed for assigned work |

Public registration in the reference creates **customer accounts only**. Creating privileged staff accounts is intended to be an internal management function.

## Security and privacy

The website's design treats account protection and access to private records as part of its core functionality.

| Area | Approach |
|---|---|
| **Passwords** | The authentication reference stores salted hashes using PHP `password_hash()` and checks them using `password_verify()` |
| **Database input** | Authentication queries use PDO prepared statements with submitted values passed separately from SQL |
| **Form data and output** | The reference validates submitted fields on the server and encodes dynamic text displayed in HTML |
| **Sessions** | Basic server-side sessions identify logged-in customers; successful login changes the session identifier |
| **Role and record access** | Full application workflows will require server-side role checks and ownership checks |
| **Prescription files** | Planned controls include file-type/size checks and access restricted to authorized users |
| **Sensitive actions** | CSRF protection, login rate limiting, inactivity expiry and audit trails remain planned security work |

These measures address different risks. Password hashing is not reversible encryption, and a hidden interface button is not a substitute for server-side authorization.

## Current implementation

The repository pack includes the original login/signup frontend and a PHP reference for customer registration, login, logout and an account confirmation page. The reference also includes a shared page layout, a jQuery Show password control, basic home/About/FAQ content and an email-uniqueness migration.

The broader pharmacy features described above remain planned. Browser layout, PHP execution and database/authentication behavior require local verification. This is a demonstration application in development, not an operating online pharmacy.

## Built with

| Layer | Technology |
|---|---|
| Page structure | HTML5 |
| Visual styling | CSS |
| Browser interaction | JavaScript and jQuery |
| Server logic | PHP with PDO |
| Database | MySQL / MariaDB |
| Collaboration | Git and GitHub |

## NOTE: 
This is a demonstration app, not a production deployment.
