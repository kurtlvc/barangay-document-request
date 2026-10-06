document.addEventListener('DOMContentLoaded', () => {
    const cancelButton = document.getElementById('confirmCancelRequest');
    const errorMessage = document.getElementById('cancelRequestError');
    if (!cancelButton || !errorMessage) return;

    cancelButton.addEventListener('click', async () => {
        cancelButton.disabled = true;
        errorMessage.hidden = true;

        try {
            const response = await fetch('../api/requests.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    action: 'cancel',
                    request_id: Number(cancelButton.dataset.requestId)
                })
            });
            const result = await response.json();
            if (!response.ok || result.status !== 'ok') throw new Error(result.message || 'Unable to cancel the request.');
            window.location.reload();
        } catch (error) {
            errorMessage.textContent = error.message;
            errorMessage.hidden = false;
            cancelButton.disabled = false;
        }
    });
});