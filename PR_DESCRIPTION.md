Title: feat: Integrate Stripe payment (fixed 20€) and confirmation email

Summary
- Adds StripeService (src/Service/StripeService.php)
- Adds PaymentController with endpoints:
  - GET /pay -> payment page (templates/payment/pay.html.twig)
  - POST /payment/intent -> creates PaymentIntent for fixed 20€ and returns client_secret
  - POST /payment/webhook -> receives Stripe webhooks and sends confirmation email when payment_intent.succeeded
- Adds public JS at public/js/payment.js to integrate Stripe.js on the frontend

Notes and setup
- STRIPE_SECRET_KEY and STRIPE_WEBHOOK_SECRET must be set in .env (test keys only). Also set STRIPE_PUBLISHABLE_KEY for frontend.
- The project currently references a local repository ../Codi-Llibreria in composer.json; composer install for stripe/stripe-php failed due to that local repo missing and missing ext-sodium on the CLI environment.

How to install dependencies locally (recommended):
1. Ensure ../Codi-Llibreria exists (clone the library into the parent folder) or restore the original path repository reference.
2. Install/enable ext-sodium in your PHP CLI or run composer with --ignore-platform-req=ext-sodium (not recommended for production).
3. Run: composer require stripe/stripe-php

How to test locally
- Start Symfony server: symfony server:start
- Visit: http://localhost:8000/pay
- Enter an email and a Stripe test card (e.g., 4242 4242 4242 4242, any CVC and future date) to simulate a payment.
- Run stripe CLI to forward webhooks or register webhook URL in Stripe dashboard: stripe listen --forward-to localhost:8000/payment/webhook

PR notes
- The branch feature/stripe-payment contains all code changes. The branch is pushed to origin/feature/stripe-payment.
- I could not run composer require stripe/stripe-php in this environment due to the above-mentioned blockers; install instructions are included.

PR checklist
- [ ] Verify composer dependencies installed and run composer update if necessary
- [ ] Set STRIPE_* env vars in .env (local test keys)
- [ ] Configure MAILER_DSN in .env to enable sending emails
- [ ] Register webhook in Stripe or use stripe CLI

Create PR URL (manual):
https://github.com/Sistemes-de-comerc-electronic/lab-6-adri2003m4/pull/new/feature/stripe-payment
