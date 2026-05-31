document .addEventListener('DOMContentLoaded', () => {
    
    // текущая дата, используется с class="fill-today"
    const todayInputs = document.querySelectorAll('.fill-today');

    if (!todayInputs.length) {
    return;
    }

    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() +1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const formatted = `${year}-${month}-${day}`;

    todayInputs.forEach(input => {
    if (!input.value) {
    input.value = formatted;
    }
    });
});