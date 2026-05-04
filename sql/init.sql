-- ======================
-- Patients
-- ======================
CREATE TABLE patients (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    medical_card_number TEXT,

    last_name TEXT NOT NULL,
    first_name TEXT NOT NULL,
    middle_name TEXT,
    birth_date DATE NOT NULL,
    gender TEXT,

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
-- Visits
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

    employer_id INT NULL
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

CREATE TABLE employers (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    inn VARCHAR(12),
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
-- Индексы (ускорение)
-- ======================

CREATE INDEX idx_patients_card_number ON patients(medical_card_number);
CREATE INDEX idx_visits_patient_id ON visits(patient_id);
CREATE INDEX idx_hazard_code ON hazard_factors(code);