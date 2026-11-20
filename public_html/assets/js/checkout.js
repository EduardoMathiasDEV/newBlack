const pay = document.querySelector('#pay');
const statusMessage = document.querySelector('#payment-status');

function showStatus(message) {
  statusMessage.textContent = message;
  statusMessage.hidden = false;
}

pay.addEventListener('click', async () => {
  if (pay.disabled) return;
  pay.disabled = true;
  showStatus('Gerando o link de pagamento…');

  try {
    const response = await fetch('criar-pagamento.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: '{}',
      signal: AbortSignal.timeout(20000),
    });

    if (!response.ok) {
      showStatus(response.status === 503
        ? 'O pagamento ainda está indisponível.'
        : 'Não foi possível gerar o link. Tente novamente.');
      pay.disabled = false;
      return;
    }

    const data = await response.json();
    const checkout = new URL(data.checkout_url);
    if (checkout.protocol !== 'https:') throw new Error();
    showStatus('Abrindo o gateway de pagamento…');
    window.location.assign(checkout.href);
  } catch {
    showStatus('Não foi possível abrir o pagamento. Tente novamente.');
    pay.disabled = false;
  }
});

window.addEventListener('pageshow', (event) => {
  if (!event.persisted) return;
  pay.disabled = false;
  statusMessage.hidden = true;
});
