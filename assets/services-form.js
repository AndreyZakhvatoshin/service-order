export class ServicesForm {
    constructor() {
        this.priceSpan = document.getElementById('service-price');
        this.serviceSelect = document.getElementById(window.serviceData.serviceSelectId);
        this.services = window.serviceData.services;
        console.log('run')
        this.init();
    }

    init() {
        if (this.serviceSelect && this.priceSpan) {
            this.serviceSelect.addEventListener('change', () => this.updatePrice());
            this.updatePrice();
        }
    }

    updatePrice() {
        const selectedId = this.serviceSelect.value;
        this.priceSpan.textContent = this.services[selectedId]?.price || '0.00';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ServicesForm();
});
