document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    const cancelButton = document.getElementById('confirmCancelRequest');
    const errorMessage = document.getElementById('cancelRequestError');
    if (cancelButton && errorMessage) {
        cancelButton.addEventListener('click', async () => {
            cancelButton.disabled = true;
            errorMessage.hidden = true;

            try {
                const response = await fetch('../api/requests.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken()
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
    }

    const editButton = document.getElementById('confirmEditPurpose');
    const editInput = document.getElementById('editPurposeText');
    const editError = document.getElementById('editPurposeError');
    if (editButton && editInput && editError) {
        editButton.addEventListener('click', async () => {
            const purpose = editInput.value.trim();
            if (purpose === '') {
                editError.textContent = 'Purpose of request is required.';
                editError.hidden = false;
                return;
            }
            editButton.disabled = true;
            editError.hidden = true;

            try {
                const response = await fetch('../api/requests.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken()
                    },
                    body: JSON.stringify({
                        action: 'update-purpose',
                        request_id: Number(editButton.dataset.requestId),
                        purpose
                    })
                });
                const result = await response.json();
                if (!response.ok || result.status !== 'ok') throw new Error(result.message || 'Unable to update the request.');
                window.location.reload();
            } catch (error) {
                editError.textContent = error.message;
                editError.hidden = false;
                editButton.disabled = false;
            }
        });
    }
});