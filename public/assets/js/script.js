const dropdownElementList = document.querySelectorAll('.dropdown-menu');
const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));

document.addEventListener("DOMContentLoaded", function () {
    const navbar = document.getElementById("navbarCollapse");
    const links = navbar.querySelectorAll(".nav-link");

    links.forEach(function (link) {
        link.addEventListener("click", function () {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(navbar);

            bsCollapse.hide();
        });
    });
});

const requestedPane = document.querySelector(window.location.hash);
if (requestedPane) {
    const requestedTab = document.querySelector(`[data-bs-target="#${requestedPane.id}"]`);
    if (requestedTab) bootstrap.Tab.getOrCreateInstance(requestedTab).show();
}

let currentStep = 1;
const totalSteps = 3;
const registerForm = document.getElementById('registerForm');
const previousButton = document.getElementById('prevBtn');
const nextButton = document.getElementById('nextBtn');

function setStep(step) {
    currentStep = Math.min(Math.max(step, 1), totalSteps);
    document.querySelectorAll('.form-step').forEach((pane, index) => {
        pane.classList.toggle('active', index + 1 === currentStep);
    });
    document.querySelectorAll('.step-item').forEach((item, index) => {
        item.classList.toggle('active', index + 1 === currentStep);
        item.classList.toggle('done', index + 1 < currentStep);
    });
    previousButton.disabled = currentStep === 1;
    nextButton.textContent = currentStep === totalSteps ? 'Create Account' : 'Next';
}

nextButton.addEventListener('click', () => {
    const currentPane = document.getElementById('step' + currentStep);
    const fields = [...currentPane.querySelectorAll('input')];
    if (!fields.every(field => field.reportValidity())) return;
    if (currentStep < totalSteps) setStep(currentStep + 1);
    else registerForm.requestSubmit();
});

previousButton.addEventListener('click', () => setStep(currentStep - 1));

const resident = document.getElementsByClassName('resident-sidebar');
const staff = document.getElementsByClassName('staff-sidebar');
const admin = document.getElementsByClassName('admin-sidebar');
