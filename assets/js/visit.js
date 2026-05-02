document.addEventListener('DOMContentLoaded', function () {

    // текущая дата
    const today = new Date();
    document.getElementById('today').value =
        today.toLocaleDateString('ru-RU');
        
    const need = document.getElementById('psychiatricExam');
    const point = document.getElementById('psychiatricFactors');

    function togglePsy() {
        if (need.value === 'yes') {
            point.disabled = false;
            point.parentElement.style.opacity = '1';
        } else {
            point.disabled = true;
            point.value = '';
            point.parentElement.style.opacity = '0.5';
        }
    }

    need.addEventListener('change', togglePsy);

    togglePsy(); // инициализация при загрузке
});