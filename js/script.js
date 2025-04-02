document.addEventListener('DOMContentLoaded', () => {
    const procurementForm = document.getElementById('procurementForm');
    if (procurementForm) {
        procurementForm.addEventListener('submit', (e) => {
            e.preventDefault(); // Prevent form submission until validation passes

            // Get form values
            const produceName = document.getElementById('produce_name').value.trim();
            const produceType = document.getElementById('produce_type').value;
            const procurementDate = document.getElementById('procurement_date').value;
            const procurementTime = document.getElementById('procurement_time').value;
            const tonnage = parseFloat(document.getElementById('tonnage').value);
            const cost = parseFloat(document.getElementById('cost').value);
            const dealerName = document.getElementById('dealer_name').value.trim();
            const branch = document.getElementById('branch').value;
            const contact = document.getElementById('contact').value.trim();
            const sellingPrice = parseFloat(document.getElementById('selling_price').value);

            // Validation
            let errors = [];

            if (!produceName) errors.push("Produce name is required.");
            if (!['beans', 'maize', 'cowpeas', 'g-nuts', 'rice', 'soybeans'].includes(produceType)) {
                errors.push("Invalid produce type.");
            }
            if (!/^\d{4}-\d{2}-\d{2}$/.test(procurementDate)) errors.push("Invalid date format.");
            if (!/^\d{2}:\d{2}$/.test(procurementTime)) errors.push("Invalid time format.");
            if (isNaN(tonnage) || tonnage < 1) errors.push("Tonnage must be at least 1 ton.");
            if (isNaN(cost) || cost < 0) errors.push("Cost cannot be negative.");
            if (!dealerName) errors.push("Dealer name is required.");
            if (!['Branch_A', 'Branch_B'].includes(branch)) errors.push("Invalid branch.");
            if (!/^\+?\d{10,15}$/.test(contact)) errors.push("Invalid phone number (10-15 digits, optional +).");
            if (isNaN(sellingPrice) || sellingPrice < 0) errors.push("Selling price cannot be negative.");

            // Display errors or submit form
            const errorDiv = document.getElementById('form-errors');
            errorDiv.innerHTML = '';
            if (errors.length > 0) {
                errorDiv.innerHTML = '<ul>' + errors.map(err => `<li>${err}</li>`).join('') + '</ul>';
            } else {
                procurementForm.submit(); // Submit if no errors
            }
        });
    }
});