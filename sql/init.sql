-- ======================
-- Пациенты
-- ======================
CREATE TABLE patients (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMP DEFAULT NOW(),
    medical_card_number TEXT, --ДДММГГФИО

    last_name TEXT NOT NULL,
    first_name TEXT NOT NULL,
    middle_name TEXT,
    birth_date DATE NOT NULL,
    gender VARCHAR(10) CHECK (gender IN ('male','female')),

    document_type TEXT,
    document_series TEXT,
    document_number TEXT,
    document_authority TEXT,
    document_authority_code TEXT,
    document_date TEXT,
    snils TEXT UNIQUE,

    phone_number TEXT,
    email TEXT,

    region TEXT,
    district TEXT,
    locality TEXT,
    street TEXT,
    house TEXT,
    building TEXT,
    flat TEXT
);

-- ======================
-- Медицинский осмотр (со снимком данных организации на момент создания)
-- ======================
CREATE TABLE visits (
    id SERIAL PRIMARY KEY,
    patient_id INT NOT NULL REFERENCES patients(id) ON DELETE CASCADE,

    exam_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    exam_type VARCHAR(50),

    organization_name TEXT,
    organization_department TEXT,
    position TEXT,

    inn TEXT,
    ogrn TEXT,
    okvd TEXT,
    employer_phone TEXT,
    employer_email TEXT,

    employer_region TEXT,
    employer_district TEXT,
    employer_locality TEXT,
    employer_street TEXT,
    employer_house TEXT,
    employer_building TEXT,
    employer_flat TEXT,

    psychiatric_exam BOOLEAN DEFAULT FALSE,
    psychiatric_factors TEXT,

    employer_id INT REFERENCES employers(id) ON DELETE SET NULL,
    estimated_cost NUMERIC(10,2)
);

-- ======================
-- Справочник вредных факторов
-- ======================
CREATE TABLE hazard_factors (
    id SERIAL PRIMARY KEY,
    code TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL
);

-- ======================
-- Связь визит ↔ вредные факторы
-- ======================
CREATE TABLE visit_hazard_factors (
    id SERIAL PRIMARY KEY,
    visit_id INT NOT NULL REFERENCES visits(id) ON DELETE CASCADE,
    hazard_factor_id INT NOT NULL REFERENCES hazard_factors(id) ON DELETE CASCADE,

    UNIQUE (visit_id, hazard_factor_id)
);

-- ======================
-- Справочник пунктов психиатрического освидетельствования
-- ======================
CREATE TABLE psychiatric_factors (
    id SERIAL PRIMARY KEY,
    code TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL
);

-- ======================
-- Связь визит ↔ психиатрические пункты
-- ======================
CREATE TABLE visit_psychiatric_factors (
    id SERIAL PRIMARY KEY,
    visit_id INT NOT NULL REFERENCES visits(id) ON DELETE CASCADE,
    psychiatric_factor_id INT NOT NULL REFERENCES psychiatric_factors(id) ON DELETE CASCADE,

    UNIQUE (visit_id, psychiatric_factor_id)
);

-- ======================
-- Справочник работодателей
-- ======================
CREATE TABLE employers (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    inn VARCHAR(12) UNIQUE,
    ogrn VARCHAR(15),
    okvd TEXT,
    phone TEXT,
    email TEXT,
    region TEXT,
    district TEXT,
    locality TEXT,
    street TEXT,
    house TEXT,
    building TEXT,
    flat TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- ======================
-- Справочник услуг 
-- ======================
CREATE TABLE requirements (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    type VARCHAR(50) CHECK (type IN ('lab', 'exam', 'instr')),
    is_global BOOLEAN NOT NULL DEFAULT FALSE,
    min_age INT,	
    gender VARCHAR(10) CHECK (gender IN ('male', 'female')),
    room TEXT,
    comment TEXT,
    sort_order INT DEFAULT 100
);

-- ======================
-- Связь вредный фактор - услуги
-- ======================
CREATE TABLE factor_requirements (
    id SERIAL PRIMARY KEY,
    hazard_factor_id INT NOT NULL REFERENCES hazard_factors(id) ON DELETE CASCADE,
    requirement_id INT NOT NULL REFERENCES requirements(id) ON DELETE CASCADE,
    period_years INT CHECK (period_years > 0),
    exam_type VARCHAR(50) CHECK (exam_type IN ('periodic', 'preliminary', 'ad-hoc'))
);

-- ======================
-- Санаторно-курортное лечение
-- ======================
CREATE TABLE patient_documents (
    id SERIAL PRIMARY KEY,
    patient_id INT REFERENCES patients(id),

    exam_date DATE,
    diagnosis TEXT,
    icd TEXT,
    complaints TEXT,
    anamnesis TEXT,

    need_card BOOLEAN,
    need_certificate BOOLEAN,

    data JSONB,

    created_at TIMESTAMP DEFAULT NOW()
);

-- ======================
-- Справочник цен
-- ======================
CREATE TABLE requirement_prices (
    id SERIAL PRIMARY KEY,

    requirement_id INT NOT NULL
        REFERENCES requirements(id)
        ON DELETE CASCADE,

    price NUMERIC(10,2) NOT NULL
        CHECK (price >= 0),

    valid_from DATE NOT NULL DEFAULT CURRENT_DATE,

    created_at TIMESTAMP DEFAULT NOW(),

    UNIQUE (requirement_id, valid_from)
);

-- ======================
-- Справочник врачей / членов ВК
-- ======================
CREATE TABLE doctors (
    id SERIAL PRIMARY KEY,

    full_name TEXT NOT NULL,
    position TEXT,

    is_vk_member BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT NOW()
);

-- ======================
-- Заключения ВК
-- ======================
CREATE TABLE vk_conclusions (
    id SERIAL PRIMARY KEY,

    patient_id INT NOT NULL
        REFERENCES patients(id)
        ON DELETE CASCADE,

    visit_id INT
        REFERENCES visits(id)
        ON DELETE SET NULL,

    protocol_date DATE NOT NULL,
    protocol_number TEXT NOT NULL,

    diagnosis TEXT,

    -- fit | temporary | permanent
    decision VARCHAR(20) NOT NULL
        CHECK (decision IN ('fit', 'temporary', 'permanent')),

    temporary_until DATE,
    temporary_reason TEXT,
    temporary_recommendations TEXT,

    chairman_id INT REFERENCES doctors(id),
    member1_id INT REFERENCES doctors(id),
    member2_id INT REFERENCES doctors(id),
    member3_id INT REFERENCES doctors(id),

    created_at TIMESTAMP DEFAULT NOW()
);

-- ======================
-- Связь ВК ↔ противопоказанные факторы
-- ======================
CREATE TABLE vk_conclusion_factors (
    id SERIAL PRIMARY KEY,

    vk_conclusion_id INT NOT NULL
        REFERENCES vk_conclusions(id)
        ON DELETE CASCADE,

    hazard_factor_id INT NOT NULL
        REFERENCES hazard_factors(id)
        ON DELETE CASCADE,

    UNIQUE (vk_conclusion_id, hazard_factor_id)
);

-- ======================
-- Пользователи (роли)
-- ======================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,

    login TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,

    full_name TEXT NOT NULL,

    role TEXT NOT NULL CHECK (
        role IN (
            'admin',
            'doctor',
            'registrar'
        )
    ),

    specialization TEXT,

    created_at TIMESTAMP DEFAULT NOW()
);

-- ======================
-- Индексы (ускорение)
-- ======================
CREATE INDEX idx_patients_card_number ON patients(medical_card_number);
CREATE INDEX idx_patients_search
    ON patients (
        last_name,
        first_name,
        birth_date
    );

CREATE INDEX idx_visits_patient_id ON visits(patient_id);
CREATE INDEX idx_visits_date ON visits(exam_date);
CREATE INDEX idx_visit_hazard ON visit_hazard_factors(visit_id);
CREATE INDEX idx_visit_psych ON visit_psychiatric_factors(visit_id);
CREATE INDEX idx_factor_req ON factor_requirements(hazard_factor_id);
CREATE INDEX idx_hazard_code ON hazard_factors(code);
CREATE INDEX idx_requirement_prices_lookup
    ON requirement_prices (
        requirement_id,
        valid_from DESC
    );
CREATE INDEX idx_factor_req_lookup
    ON factor_requirements (
        hazard_factor_id,
        exam_type,
        requirement_id
    );
CREATE INDEX idx_visit_hazard_visit
    ON visit_hazard_factors (
        visit_id,
        hazard_factor_id
    );