```
    %% --- AUTH ---
    USERS ||--o{ ACTIVITY_LOGS : records
    USERS ||--o{ CITIZEN_MESSAGES : sends
    
    USERS {
        id pk
        string role "admin, resident, treasurer"
        string registration_status "pending, verified, rejected"
        string email
    }

    CITIZENS {
        id pk
        string status "permanent, moved, deceased"
        string id_card_photo "Foto KTP"
        string verified_selfie_photo "Foto Selfie Valid (Untuk Login)"
    }

    HOUSES ||--o{ FAMILIES : accommodates
    
    FAMILIES ||--o{ MUTATIONS : undergoes
    FAMILIES ||--o{ BILLINGS : billed_for

    FAMILIES {
        id pk
        enum ownership_status "owner, renter"
        string status "active, moved"
    }

    %% --- UTILS & CONTENT ---
    ANNOUNCEMENTS {
        id pk
        string title
        text content
        string image_url
        string document_url
    }

    ACTIVITIES {
        id pk
        string name
        string category
        date date
        string status
        string location "*Lokasi"
        string person_in_charge "*Penanggung Jawab"
    }

    PAYMENT_CHANNELS {
        id pk
        string channel_name "Judul (BCA/Gopay)"
        string type "*Bank/E-Wallet"
        string account_number "No Rekening"
        string account_name "*Atas Nama"
        string thumbnail
        string qr_code "*QRIS"
        text notes "*Catatan"
    }

    %% --- FINANCE ---
    DUES_TYPES ||--o{ BILLINGS : generates
    TRANSACTION_CATEGORIES ||--o{ TRANSACTIONS : categorizes
    
    DUES_TYPES {
        id pk
        string name "Iuran Sampah, dll"
        decimal amount
    }

    BILLINGS {
        id pk
        family_id fk
        string billing_code
        string period "*Periode (Okt 2025)"
        status "paid, unpaid"
    }

    TRANSACTIONS {
        id pk
        enum type "income, expense"
        decimal amount
        string proof_image "Bukti"
        date transaction_date
    }
