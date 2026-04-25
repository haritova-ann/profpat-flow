document.addEventListener('DOMContentLoaded', function () {

    // текущая дата
    const today = new Date();
    document.getElementById('today').value =
        today.toLocaleDateString('ru-RU');

    // генерация АК
    function generateAK() {
        const birth = document.getElementById('birthDate').value;
        const l = document.getElementById('lastName').value[0] || '';
        const f = document.getElementById('firstName').value[0] || '';
        const m = document.getElementById('middleName').value[0] || '';

        if (birth.length === 10) {
            const parts = birth.split('.');
            const ak = parts[0] + parts[1] + parts[2].slice(-2) +
                       l.toUpperCase() + f.toUpperCase() + m.toUpperCase();

            document.getElementById('ak').value = ak;
        }
    }

    document.querySelectorAll('#birthDate, #lastName, #firstName, #middleName')
        .forEach(el => el.addEventListener('input', generateAK));

    // телефон
    const input = document.getElementById('phoneNumber');
    input.addEventListener('input', function (e) {
        let value = e.target.value;
        let digits = value.replace(/\D/g, '');

        if (digits.startsWith('8')) {
            digits = '7' + digits.slice(1);
        }

        if (!digits.startsWith('7')) {
            digits = '7' + digits;
        }

        digits = digits.substring(0, 11);

        let formatted = '+7';

        if (digits.length > 1) formatted += ' ' + digits.substring(1, 4);
        if (digits.length >= 5) formatted += ' ' + digits.substring(4, 7);
        if (digits.length >= 8) formatted += ' ' + digits.substring(7, 9);
        if (digits.length >= 10) formatted += ' ' + digits.substring(9, 11);

        e.target.value = formatted;
    });

    // СНИЛС
    document.getElementById('snils').addEventListener('input', function(e) {
        let x = e.target.value.replace(/\D/g, '').substring(0,11);
        let result = '';
        if (x.length > 0) result += x.substring(0,3);
        if (x.length >= 4) result += ' ' + x.substring(3,6);
        if (x.length >= 7) result += ' ' + x.substring(6,9);
        if (x.length >= 10) result += ' ' + x.substring(9,11);
        e.target.value = result;
    });

    // дата рождения
    document.getElementById('birthDate').addEventListener('input', function(e) {
        let x = e.target.value.replace(/\D/g, '').slice(0, 8);
        let formatted = '';
        if (x.length > 0) formatted += x.slice(0, 2);
        if (x.length >= 3) formatted += '.' + x.slice(2, 4);
        if (x.length >= 5) formatted += '.' + x.slice(4, 8);

        e.target.value = formatted;
    });

});