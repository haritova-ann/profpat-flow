document.addEventListener('DOMContentLoaded', function () {
    // Кнопка заполнения из предыдущего визита
    const fillFromPreviousBtn = document.querySelector('button[data-action="fill-from-previous-visit"]');
    console.log('Кнопка найдена:', fillFromPreviousBtn); // Отладочный вывод

    if (fillFromPreviousBtn) {
        fillFromPreviousBtn.addEventListener('click', async function(event) {
            console.log('Кнопка нажата'); // Отладочный вывод
            event.preventDefault(); // Предотвращаем стандартное поведение кнопки
            try {
                const patientId = document.querySelector('input[name="patient_id"]').value;
                console.log('ID пациента:', patientId); // Отладочный вывод

                const response = await fetch(`/api/last_visit.php?patient_id=${patientId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                console.log('Ответ сервера:', response); // Отладочный вывод

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                console.log('Результат:', result); // Отладочный вывод

                if (result.error) {
                    alert(result.error);
                    return;
                }

                if (result) {
                    // Заполнение полей формы
                    document.getElementById('examType').value = result.exam_type || 'periodic';
                    document.getElementById('employerSearch').value = result.organization_name || '';
                    document.getElementById('organizationName').value = result.organization_name || '';
                    document.getElementById('employerId').value = result.employer_id || '';
                    document.getElementById('organizationDepartment').value = result.organization_department || '';
                    document.getElementById('position').value = result.position || '';
                    document.getElementById('hazardFactors').value = result.hazard_factors || '';
                    document.getElementById('psychiatricExam').value = result.psychiatric_exam || 'false';
                    document.getElementById('psychiatricFactors').value = result.psychiatric_factors || '';
                    document.getElementById('inn').value = result.inn || '';
                    document.getElementById('ogrn').value = result.ogrn || '';
                    document.getElementById('okvd').value = result.okvd || '';
                    document.getElementById('phoneNumber').value = result.employer_phone || '';
                    document.getElementById('email').value = result.employer_email || '';
                    document.getElementById('region').value = result.employer_region || 'Красноярский край';
                    document.getElementById('district').value = result.employer_district || '';
                    document.getElementById('locality').value = result.employer_locality || 'г. Красноярск';
                    document.getElementById('street').value = result.employer_street || '';
                    document.getElementById('house').value = result.employer_house || '';
                    document.getElementById('building').value = result.employer_building || '';
                    document.getElementById('flat').value = result.employer_flat || '';

                    // Вызываем обработчики для динамических элементов
                    document.getElementById('psychiatricExam').dispatchEvent(new Event('change'));
                } else {
                    alert('Предыдущие визиты не найдены');
                }
            } catch (error) {
                console.error('Полная ошибка:', error);
                alert('Не удалось загрузить данные предыдущего визита: ' + error.message);
            }
        });
    } else {
        console.error('Кнопка не найдена!'); // Отладочный вывод
    }

    // текущая дата
    const today = new Date();
    document.getElementById('today').value =
        today.toLocaleDateString('ru-RU');
        
    const need = document.getElementById('psychiatricExam');
    const point = document.getElementById('psychiatricFactors');

    function togglePsy() {
        if (need.value === 'true') {
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

    const searchInput = document.getElementById('employerSearch');
    const dropdown = document.getElementById('employerDropdown');
    const employerIdInput = document.getElementById('employerId');

    searchInput.addEventListener('input', async () => {

    const q = searchInput.value.trim();

    if (q.length < 2) {
        dropdown.innerHTML = '';
        dropdown.classList.remove('show');
        return;
    }

    const res = await fetch(
        `/api/employers.php?q=${encodeURIComponent(q)}`
    );

    const data = await res.json();

    dropdown.innerHTML = '';

    if (!data.length) {
        dropdown.classList.remove('show');
        return;
    }

    data.forEach(emp => {

        const div = document.createElement('div');

        div.classList.add('dropdown-item');

        div.innerText =
            `${emp.name} (ИНН: ${emp.inn ?? '-'})`;

        div.onclick = () => selectEmployer(emp.id);

        dropdown.appendChild(div);

    });

    dropdown.classList.add('show');

});

    async function selectEmployer(id) {
        const res = await fetch(`/api/employer.php?id=${id}`);
        const emp = await res.json();

        employerIdInput.value = emp.id;

        // ВАЖНО: синхронизация обоих полей
        document.getElementById('organizationName').value = emp.name;
        document.getElementById('employerSearch').value = emp.name;

        document.getElementById('inn').value = emp.inn;
        document.getElementById('ogrn').value = emp.ogrn;
        document.getElementById('okvd').value = emp.okvd;
        document.getElementById('phoneNumber').value = emp.phone;
        document.getElementById('email').value = emp.email;
        document.getElementById('region').value = emp.region;
        document.getElementById('district').value = emp.district;
        document.getElementById('locality').value = emp.locality;
        document.getElementById('street').value = emp.street;
        document.getElementById('house').value = emp.house;
        document.getElementById('building').value = emp.building;
        document.getElementById('flat').value = emp.flat;

        dropdown.innerHTML = '';
dropdown.classList.remove('show');
    }

    document.addEventListener('click', (e) => {

    const inside =
        searchInput.contains(e.target) ||
        dropdown.contains(e.target);

    if (!inside) {
        dropdown.classList.remove('show');
    }

});

    searchInput.addEventListener('input', () => {
        employerIdInput.value = '';
        document.getElementById('organizationName').value = searchInput.value;
    });
});