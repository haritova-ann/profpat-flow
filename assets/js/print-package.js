function escapeHtml(value = '') {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatHazardCodes(hazardFactors = []) {
    return hazardFactors
        .map(item => item.code)
        .join(', ');
}

function sortHazardFactors(items = []) {
    return [...items].sort((a, b) =>
        a.code.localeCompare(b.code, undefined, { numeric: true })
    );
}

function buildMedicalCard(printData) {
    return `
        <div class="document-page medical-card">

            <header class="doc-header">
                Медицинская документация<br>
                Форма № 025/у<br>
                Утверждена приказом Министерства<br>
                здравоохранения Российской Федерации<br>
                от 13 мая 2025 г. № 274н
            </header>

            <h1 class="main-title-mc">
                МЕДИЦИНСКАЯ КАРТА ЛИЦА,<br>
                ПРОХОДЯЩЕГО ${escapeHtml(printData.exam.type)} МЕДИЦИНСКИЙ ОСМОТР<br>
                № ${escapeHtml(printData.patient.cardNumber)}
            </h1>

            <main class="doc-content">

                <div class="form-row">
                    <span class="label">Дата заполнения медицинской карты:</span>
                    <span class="value">${escapeHtml(printData.exam.date)}</span>
                </div>

                <div class="form-row">
                    <span class="label">Фамилия, имя, отчество:</span>
                    <span class="value value-bold">
                        ${escapeHtml(printData.patient.fullName)}
                    </span>
                </div>

                <div class="form-row inline-group">
                    <div class="inline-item">
                        <span class="label">Дата рождения:</span>
                        <span class="value value-bold">
                            ${escapeHtml(printData.patient.birthDate)}
                        </span>
                    </div>

                    <div class="inline-item">
                        <span class="label">Пол:</span>
                        <span class="value">
                            ${escapeHtml(printData.patient.gender)}
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <span class="label">Документ, удостоверяющий личность:</span>
                    <span class="value">
                        ${escapeHtml(printData.patient.document)}
                    </span>
                </div>

                <div class="form-row inline-group">
                    <div class="inline-item">
                        <span class="label">Номер телефона:</span>
                        <span class="value">
                            ${escapeHtml(printData.patient.phone)}
                        </span>
                    </div>

                    <div class="inline-item">
                        <span class="label">Адрес электронной почты:</span>
                        <span class="value">
                            ${escapeHtml(printData.patient.email)}
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <span class="label">Регистрация по месту жительства:</span>
                    <span class="value">
                        ${escapeHtml(printData.patient.address)}
                    </span>
                </div>

                <div class="form-row">
                    <span class="label">СНИЛС:</span>
                    <span class="value value-bold">
                        ${escapeHtml(printData.patient.snils)}
                    </span>
                </div>

                <div class="form-row">
                    <span class="label">Место работы, учебы:</span>
                    <span class="value value-bold">
                        ${escapeHtml(printData.employer.name)}
                    </span>
                </div>

                <div class="form-row">
                    <span class="label">Структурное подразделение:</span>
                    <span class="value value-bold">
                        ${escapeHtml(printData.employer.department)}
                    </span>
                </div>

                <div class="form-row">
                    <span class="label font-bold">Должность (профессия):</span>
                    <span class="value value-bold">
                        ${escapeHtml(printData.employer.position)}
                    </span>
                </div>

                <div class="form-row">
                    <span class="label font-bold">
                        Вид работы, выполняемой работником:</span>
                    <span class="value value-bold">
                        ${escapeHtml(formatHazardCodes(sortHazardFactors(printData.exam.hazardFactors)))}
                    </span>
                </div>

                <div class="form-row">
                    <span class="label">
                        Проведение добровольного психиатрического освидетельствования:
                    </span>
                    <span class="value value-bold">
                        ${escapeHtml(printData.exam.psychiatricExam)}
                    </span>
                </div>

            </main>
        </div>
    `;
}

function buildConsentPage(printData) {
    const consentHeader = () => `
        <div class="consent-header">
            <p>
                Я нижеподписавшийся
                <span class="consent-line">
                    ${escapeHtml(printData.patient.fullName)}
                </span>
            </p>

            <p>
                Документ удостоверяющий личность
                <span class="consent-line">
                    ${escapeHtml(printData.patient.document)}
                </span>
            </p>

            <p>
                Зарегистрированный(ая) по адресу
                <span class="consent-line">
                    ${escapeHtml(printData.patient.address)}
                </span>
            </p>
        </div>
    `;

    const consentSignatures = () => `
        <div class="consent-signatures">

            <div class="signature-row">
                <div class="signature-field signature-short">
                    <span class="signature-line"></span>
                    <span>(подпись)</span>
                </div>

                <div class="signature-field signature-wide">
                    <span class="signature-line">
                        ${escapeHtml(printData.patient.fullName)}
                    </span>
                    <span>
                        (Ф. И. О. гражданина или законного представителя гражданина)
                    </span>
                </div>
            </div>

            <div class="signature-row">
                <div class="signature-field signature-short">
                    <span class="signature-line"></span>
                    <span>(подпись)</span>
                </div>

                <div class="signature-field signature-wide">
                    <span class="signature-line"></span>
                    <span>
                        (Ф. И. О. медицинского работника)
                    </span>
                </div>
            </div>

            <div class="signature-date">
                <span class="date-line">
                    ${escapeHtml(printData.exam.date)}
                </span>
                <span>(дата оформления)</span>
            </div>

        </div>
    `;

    const medicalConsent = () => `
        <section class="consent-block medical-consent">

            <h1 class="consent-title">
                Информированное добровольное согласие на виды медицинских
                вмешательств, включенные в Перечень определенных видов
                медицинских вмешательств, на которые граждане дают
                информированное добровольное согласие при выборе врача
                и медицинской организации для получения первичной
                медико-санитарной помощи
            </h1>

            ${consentHeader()}

            <div class="consent-text">

                <p>
                    даю информированное добровольное согласие на виды медицинских вмешательств,
                    включенные в Перечень определенных видов медицинских вмешательств,
                    на которые граждане дают информированное добровольное согласие при выборе врача
                    и медицинской организации для получения первичной медико-санитарной помощи,
                     утвержденный приказом Министерства здравоохранения и социального развития
                      Российской Федерации от 23 апреля 2012 г. № 390н (зарегистрирован Министерством
                       юстиции Российской Федерации 5 мая 2012 г. № 24082) (далее — Перечень), для
                       получения первичной медико-санитарной помощи/получения первичной 
                       медико-санитарной помощи лицом, законным представителем которого я являюсь 
                       (ненужное зачеркнуть) в Общество с ограниченной ответственностью 
                       «Центр квантовой медицины №1» (сокращенное наименование ООО «ЦКМ №1, 
                       медицинским работником  в доступной для меня форме мне разъяснены цели, 
                       методы оказания медицинской помощи, связанный с ними риск, возможные 
                       варианты медицинских вмешательств, их последствия, в том числе вероятность 
                       развития осложнений, а также предполагаемые результаты оказания медицинской 
                       помощи. Мне разъяснено, что я имею право отказаться от одного или нескольких 
                       видов медицинских вмешательств, включенных в Перечень, или потребовать его 
                       (их) прекращения, за исключением случаев, предусмотренных частью 9 статьи 20
                        Федерального закона от 21 ноября 2011 г. № 323-ФЗ «Об основах охраны здоровья
                         граждан в Российской Федерации» (Собрание законодательства Российской 
                         Федерации, 2011, № 48, ст. 6724; 2012, № 26, ст. 3442, 3446).
                </p>

                <p>
                    Мне разъяснено, что я имею право отказаться от одного
                    или нескольких видов медицинских вмешательств, включенных
                    в Перечень, или потребовать его (их) прекращения, за
                    исключением случаев, предусмотренных частью 9 статьи 20
                    Федерального закона от 21 ноября 2011 г. № 323-ФЗ
                    «Об основах охраны здоровья граждан в Российской Федерации»
                    (Собрание законодательства Российской Федерации, 2011,
                    № 48, ст. 6724; 2012, № 26, ст. 3442, 3446).
                </p>

                <p>
                    Сведения о выбранных мною лицах, которым в соответствии
                    с пунктом 5 части 3 статьи 19 Федерального закона
                    от 21 ноября 2011 г. № 323-ФЗ «Об основах охраны здоровья
                    граждан в Российской Федерации» может быть передана
                    информация о состоянии моего здоровья или состоянии лица,
                    законным представителем которого я являюсь
                    (ненужное зачеркнуть):
                </p>

            </div>

            ${consentSignatures()}

        </section>
    `;

    const personalDataConsent = () => `
        <section class="consent-block personal-data-consent">

            <h1 class="consent-title personal-data-title">
                Согласие на обработку персональных данных
            </h1>

            ${consentHeader()}

            <div class="consent-text">

                <p>
                    в соответствии с требованиями статьи 9 федерального закона от 27.07.2006 
                    г. «О персональных данных» №152-ФЗ , подтверждаю свое согласие ООО «ЦКМ  №1»
                     (далее Оператор), зарегистрированному по адресу: г. Красноярск, ул. Калинина,
                      д. 41
                    , включающих: фамилия, имя, отчество, дата рождения,
                    паспортные данные, контактный телефон (дом., сотовый,
                    рабочий), фактический адрес проживания, реквизиты полиса
                    ОМС, СНИЛС, место работы, должность, гражданство,
                    национальность, данные о состоянии здоровья, заболеваниях,
                    случаях обращения за медицинской помощью в медико-профилактических
                    целях, в целях установления медицинского диагноза и оказания
                    медицинских услуг при условии, что их обработка осуществляется
                    лицом, профессионально занимающимся медицинской деятельностью
                    и обязанным сохранять врачебную тайну.
                </p>

                <p>
                    В процессе оказания Оператором медицинской помощи предоставлять
                    право медицинским работникам передавать персональные данные,
                    содержащие сведения, составляющие врачебную тайну, другим
                    должностным лицам Оператора, в интересах лечения и обследования.
                </p>

                <p>
                    Предоставить Оператору право осуществлять все действия
                    (операции) с персональными данными, включая сбор,
                    систематизацию, накопление, хранение, уточнение
                    (обновление, изменение), использование, обезличивание,
                    блокирование, уничтожение.
                </p>

                <p>
                    Оператор вправе обрабатывать мои персональные данные
                    посредством внесения их в электронную базу данных,
                    включения в списки (реестры) и отчетные формы,
                    предусмотренные документами, регламентирующими предоставление
                    отчетных данных (документов) по ОМС (договором ДМС).
                </p>

                <p>
                    Оператор имеет право во исполнение своих обязанностей
                    по работе в системе ОМС (по договору ДМС) на обмен
                    (прием и передачу) персональных данных со страховой
                    медицинской организацией и территориальным фондом ОМС
                    с использованием машинных носителей или по каналам связи,
                    с соблюдением мер, обеспечивающих их защиту от
                    несанкционированного доступа, при условии, что их прием
                    и обработка будут осуществляться лицом, обязанным
                    сохранять профессиональную тайну.
                </p>

                <p>
                    Срок хранения персональных данных соответствует сроку
                    хранения первичных медицинских документов (медицинской карты).
                    Передача персональных данных иным лицам или иное их
                    разглашение может осуществляться только с моего
                    письменного согласия.
                </p>

                <p>
                    Настоящее согласие дано мной. Я оставляю за собой право
                    отозвать свое согласие посредством составления соответствующего
                    письменного документа, который может быть направлен в адрес
                    оператора по почте заказным письмом с уведомлением о вручении,
                    либо вручен лично под расписку представителю Оператора.
                    В случае получения письменного заявления об отзыве настоящего
                    согласия на обработку персональных данных Оператор обязан
                    прекратить их обработку в течение периода времени,
                    необходимого для завершения взаиморасчетов по оплате
                    оказанной до этого медицинской помощи.
                </p>

                <p>
                    Подтверждаю, что ознакомлен(а) с положениями Федерального
                    закона от 27.07.2006 № 152-ФЗ «О персональных данных»,
                    права и обязанности в области защиты персональных данных
                    мне разъяснены.
                </p>

            </div>

            ${consentSignatures()}

        </section>
    `;

    return `
        <div class="document-page font-times consent-page">

            ${medicalConsent()}

            ${personalDataConsent()}

        </div>
    `;
}

function buildConclusion(printData) {
    const items = sortHazardFactors(printData.exam.hazardFactors);
    
    const density = items.length > 17 ? 'tight' : '';

    const hazardRows = items.map(item => `
        <tr>
            <td>${escapeHtml(item.code)}</td>
            <td>${escapeHtml(item.name)}</td>
        </tr>
    `).join('');

    return `
        <div class="document-page conclusion-page ${density}">
        
        <!-- Левый верхний угловой штамп клиники -->
        <header class="cl-stamp">
            Общество с ограниченной ответственностью<br>
            «Центр квантовой медицины №1» (ООО ЦКМ №1)<br>
            660048, г. Красноярск, ул. Калинина 41<br>
            ОГРН 1032401796250 ИНН 2460060098 КПП 246001001<br>
            Тел. 8 (391) 22-96-511, факс 8 (391) 29-13-009 <br>
            contact@centrkvant.ru
        </header>

        <!-- Главный заголовок бланка -->
        <h1 class="cl-main-title">
            ЗАКЛЮЧЕНИЕ ПО РЕЗУЛЬТАТАМ ${escapeHtml(printData.exam.typeConclusion)}<br>
            МЕДИЦИНСКОГО ОСМОТРА (ОБСЛЕДОВАНИЯ)
        </h1>

        <!-- Основная сетка анкетных данных -->
        <div class="cl-form-container">
            
            <div class="cl-row">
                <span class="cl-label">Ф. И. О.</span>
                <div class="cl-line-fill flex-grow">
                    <span class="cl-value cl-value-bold">${escapeHtml(printData.patient.fullName)}</span>
                </div>
            </div>

            <div class="cl-row cl-inline-group">
                <div class="cl-inline-item">
                    <span class="cl-label">Дата рождения:</span>
                    <div class="cl-line-fill flex-grow">
                        <span class="cl-value cl-value-bold">${escapeHtml(printData.patient.birthDate)}</span>
                    </div>
                </div>
                <div class="cl-inline-item">
                    <span class="cl-label">Пол:</span>
                    <div class="cl-line-fill flex-grow">
                        <span class="cl-value">${escapeHtml(printData.patient.gender)}</span>
                    </div>
                </div>
            </div>

            <div class="cl-row cl-multiline-row">
                <span class="cl-label">Место работы, организация (предприятие):</span>
                <div class="cl-line-fill flex-grow">
                    <span class="cl-value cl-value-bold">${escapeHtml(printData.employer.name)}</span>
                </div>
            </div>

            <div class="cl-row">
                <span class="cl-label">Цех, участок, отдел:</span>
                <div class="cl-line-fill flex-grow">
                    <span class="cl-value">${escapeHtml(printData.employer.department)}</span>
                </div>
            </div>

            <div class="cl-row cl-multiline-row">
                <span class="cl-label">Профессия (должность) или вид работы:</span>
                <div class="cl-line-fill flex-grow">
                    <span class="cl-value">${escapeHtml(printData.employer.position)}</span>
                </div>
            </div>

            <div class="cl-law-section">
                <strong>На основании приказа №29н (Вредные производственные факторы и (или) виды работ):</strong>
            </div>

            <div class="cl-hazard-table-wrapper">
                
                <table class="cl-hazard-table">
                    <thead>
                        <tr>
                            <th>Пункт</th>
                            <th>Вредный производственный фактор и (или) вид работы</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${hazardRows}
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Раздел результатов и медицинского статуса -->
        <div class="cl-results-block">
            <p class="cl-result-intro">
                Согласно результатам проведенного <strong>${escapeHtml(printData.exam.typeFormatted)}</strong> осмотра (обследования)
            </p>
            
            <p class="cl-status-verdict">
                Медицинские противопоказания <span class="cl-underline-bold">не выявлены</span>
            </p>
        </div>

        <div class="cl-bottom-block">
        <!-- Группы здоровья (Квадраты под заполнение) -->
        <div class="cl-health-groups">
            <span class="cl-label">Группа здоровья:</span>
            <div class="cl-checkbox-box">1.</div>
            <div class="cl-checkbox-box">2.</div>
            <div class="cl-checkbox-box">3.</div>
        </div>

        
            <!-- Подписи председателя и дата заключения -->
            <div class="cl-footer-signatures">
                
                <div class="cl-sig-row">
                    <span class="cl-label font-bold">Председатель врачебной комиссии</span>
                    <div class="cl-sig-line-wrapper">
                        <span class="cl-bottom-solid-line"></span>
                    </div>
                    <span class="cl-doctor-name">И. С. Воронина</span>
                </div>

                <div class="cl-date-row">
                    <span class="cl-label">Дата</span>
                    <div class="cl-date-line-wrapper">
                        <span class="cl-bottom-solid-line"></span>
                    </div>
                </div>

            
        </div>
        <!-- Футер документа (Место печати и сноска) -->
        <footer class="cl-document-footer">
            <span class="cl-mp-stamp">М.П.</span>
            <span class="cl-footer-note">*Передается работодателю и приобщается к личному делу работника</span>
        </footer>
        </div>

    </div>
    `;
}

function buildMedicalCertificates(printData) {

    return `
    <div class="document-page certificates-page">
        
        <!-- ВЕРХНЯЯ СПРАВКА (НАРКОЛОГ) -->
        <section class="cert-block">
            <header class="cert-top-header">
                В комиссию по предварительным и периодическим осмотрам.<br>
                В соответствии с приказом Министерства<br>
                Здравоохранения РФ от 28.01.2021г. 29Н
            </header>

            <h1 class="cert-main-title">
                Справка № <span class="cert-line-short"></span><br>
                <span class="cert-subtitle">(добровольного осмотра психиатра-нарколога)</span>
            </h1>

            <div class="cert-date-line">
                от « <span class="cert-line-day"></span> » <span class="cert-line-month"></span> 20 <span class="cert-line-year"></span> г.
            </div>

            <div class="cert-form-grid">
                <div class="cert-row">
                    <span class="cert-label">1. Ф.И.О:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.patient.fullName)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">2. Дата рождения:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.patient.birthDate)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">3. Адрес регистрации по месту жительства:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.patient.address)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">4. Место работы:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.employer.name)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">5. Должность (профессия) в настоящее время:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.employer.position)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">6. Вредные производственные факторы и (или) виды работ:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(formatHazardCodes(sortHazardFactors(printData.exam.hazardFactors)))}</span></div>
                </div>
            </div>

            <p class="cert-statement-text">
                7. На момент осмотра по профилю наркология медицинские противопоказания к выполнению работ с вредными (или) опасными условиями труда, а также работ, при выполнении которых обязательно проведение предварительных и периодических медицинских осмотров обследований) <strong>не выявлены</strong>
            </p>

            <div class="cert-footer-signatures">
                <span class="cert-label font-bold">Врач психиатр-нарколог</span>
                <div class="cert-sig-box">
                    <span class="cert-bottom-line"></span>
                    <span class="cert-sub-caption">(подпись)</span>
                </div>
                <div class="cert-name-box">
                    <span class="cert-bottom-line"></span>
                    <span class="cert-sub-caption">(Ф.И.О.)</span>
                </div>
            </div>
        </section>

        <!-- Разделитель бланков на листе -->
        <div class="cert-divider-space"></div>

        <!-- НИЖНЯЯ СПРАВКА (ПСИХИАТР) -->
        <section class="cert-block">
            <header class="cert-top-header">
                В комиссию по предварительным и периодическим осмотрам.<br>
                В соответствии с приказом Министерства<br>
                Здравоохранения РФ от 28.01.2021г. 29Н
            </header>

            <h1 class="cert-main-title">
                Справка № <span class="cert-line-short"></span><br>
                <span class="cert-subtitle">(добровольного осмотра врача-психиатра)</span>
            </h1>

            <div class="cert-date-line">
                от « <span class="cert-line-day"></span> » <span class="cert-line-month"></span> 20 <span class="cert-line-year"></span> г.
            </div>

            <div class="cert-form-grid">
                <div class="cert-row">
                    <span class="cert-label">1. Ф.И.О:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.patient.fullName)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">2. Дата рождения:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.patient.birthDate)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">3. Адрес регистрации по месту жительства:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.patient.address)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">4. Место работы:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.employer.name)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">5. Должность (профессия) в настоящее время:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(printData.employer.position)}</span></div>
                </div>
                <div class="cert-row">
                    <span class="cert-label">6. Вредные производственные факторы и (или) виды работ:</span>
                    <div class="cert-line-fill flex-grow"><span class="cert-value">${escapeHtml(formatHazardCodes(sortHazardFactors(printData.exam.hazardFactors)))}</span></div>
                </div>
            </div>

            <p class="cert-statement-text">
                7. На момент осмотра по профилю психиатрия медицинские противопоказания к выполнению работ с вредными (или) опасными условиями труда, а также работ, при выполнении которых обязательно проведение предварительных и периодических медицинских осмотров обследований) <strong>не выявлены</strong>
            </p>

            <div class="cert-footer-signatures">
                <div class="cert-doctor-type">
                    <span class="cert-label font-bold">Врач психиатр</span>
                    <span class="cert-stamp-label font-bold">Личная печать врача</span>
                </div>
                <div class="cert-sig-box">
                    <span class="cert-bottom-line"></span>
                    <span class="cert-sub-caption">(подпись)</span>
                </div>
                <div class="cert-name-box">
                    <span class="cert-bottom-line"></span>
                    <span class="cert-sub-caption">(Ф.И.О.)</span>
                </div>
            </div>
        </section>

    </div>
`;
}

function buildPrintPackage(printData) {
    return `
        ${buildMedicalCard(printData)}
        ${buildConsentPage(printData)}
        ${buildConclusion(printData)}
        ${buildMedicalCertificates(printData)}
    `;
}

function printPackage(html) {
    const iframe = document.createElement('iframe');

    iframe.style.position = 'fixed';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';

    document.body.appendChild(iframe);

    const doc = iframe.contentDocument;

    iframe.onload = () => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();

        setTimeout(() => iframe.remove(), 1000);
    };

    doc.open();
    doc.write(`
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Печатный пакет</title>
            <link rel="stylesheet" href="/assets/css/print-package.css">
        </head>
        <body>
            ${html}
        </body>
        </html>
    `);
    doc.close();
}

document
    .getElementById('print-package-btn')
    ?.addEventListener('click', () => {
        const html = buildPrintPackage(printData);
        printPackage(html);
    });