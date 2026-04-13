document.addEventListener('DOMContentLoaded', async () => {
  if (typeof STRIPE_PUBLISHABLE_KEY === 'undefined' || !STRIPE_PUBLISHABLE_KEY) {
    console.error('STRIPE_PUBLISHABLE_KEY not set. Define in .env as STRIPE_PUBLISHABLE_KEY');
    return;
  }

  const stripe = Stripe(STRIPE_PUBLISHABLE_KEY);
  const elements = stripe.elements();
  const card = elements.create('card');
  card.mount('#card-element');

  const payButton = document.getElementById('pay-button');
  payButton.addEventListener('click', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    if (!email) {
      alert('Introdueix un email');
      return;
    }

    // Request a PaymentIntent from backend
    const res = await fetch('/payment/intent', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({email})
    });

    if (!res.ok) {
      const txt = await res.text();
      alert('Error creating payment intent: ' + txt);
      return;
    }

    const data = await res.json();
    const clientSecret = data.client_secret;

    const {error, paymentIntent} = await stripe.confirmCardPayment(clientSecret, {
      payment_method: {
        card: card,
        billing_details: {email}
      }
    });

    if (error) {
      alert('Payment failed: ' + error.message);
    } else if (paymentIntent && paymentIntent.status === 'succeeded') {
      alert('Pagament completat. Rebràs un email de confirmació.');
    }
  });
});
