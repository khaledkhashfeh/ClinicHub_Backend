<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Certification;
use App\Models\City;
use App\Models\Clinic;
use App\Models\ClinicDoctor;
use App\Models\ClinicGalleryImage;
use App\Models\ClinicService;
use App\Models\District;
use App\Models\Doctor;
use App\Models\DoctorClinicSchedule;
use App\Models\Governorate;
use App\Models\Invitation;
use App\Models\LabResult;
use App\Models\LoyaltyTransaction;
use App\Models\MedicalCenter;
use App\Models\MedicalCenterGalleryImage;
use App\Models\MedicalCenterService;
use App\Models\MedicalFile;
use App\Models\MedicalSpecialization;
use App\Models\Method;
use App\Models\Offer;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Review;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ScheduleOverride;
use App\Models\ScheduleSlot;
use App\Models\Secretary;
use App\Models\Specialization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Models\VisitRecord;
use App\Models\WaitingList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class CompleteDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║         🏥 ClinicHub Complete Data Seeder 🏥            ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Step 1: Create Governorates and Cities
        $this->createGovernoratesAndCities();

        // Step 2: Create Districts
        $this->createDistricts();

        // Step 3: Create Specializations
        $this->createSpecializations();

        // Step 4: Create Medical Specializations
        $this->createMedicalSpecializations();

        // Step 5: Create Methods
        $this->createMethods();

        // Step 6: Create Users (Patients, Doctors, Secretaries)
        $this->createUsers();

        // Step 7: Create Doctors with Specializations and Certifications
        $this->createDoctors();

        // Step 8: Create Clinics
        $this->createClinics();

        // Step 9: Create Medical Centers
        $this->createMedicalCenters();

        // Step 10: Attach Clinics to Medical Centers
        $this->attachClinicsToMedicalCenters();

        // Step 11: Create Clinic-Doctor Relationships
        $this->createClinicDoctorRelationships();

        // Step 12: Create Secretaries
        $this->createSecretaries();

        // Step 13: Create Clinic Services
        $this->createClinicServices();

        // Step 14: Create Clinic Gallery Images
        $this->createClinicGalleryImages();

        // Step 15: Create Medical Center Services
        $this->createMedicalCenterServices();

        // Step 16: Create Medical Center Gallery Images
        $this->createMedicalCenterGalleryImages();

        // Step 17: Create Doctor Clinic Schedules
        $this->createDoctorClinicSchedules();

        // Step 18: Create Schedule Slots
        $this->createScheduleSlots();

        // Step 19: Create Appointments
        $this->createAppointments();

        // Step 20: Create Medical Files
        $this->createMedicalFiles();

        // Step 21: Create Visit Records
        $this->createVisitRecords();

        // Step 22: Create Prescriptions
        $this->createPrescriptions();

        // Step 23: Create Lab Results
        $this->createLabResults();

        // Step 24: Create Reviews
        $this->createReviews();

        // Step 25: Create Offers
        $this->createOffers();

        // Step 26: Create Loyalty Transactions
        $this->createLoyaltyTransactions();

        // Step 27: Ensure subscription plans exist, then create Subscriptions
        $this->call(SubscriptionPlanSeeder::class);
        $this->createSubscriptions();

        // Step 28: Create Waiting Lists
        $this->createWaitingLists();

        // Step 29: Create Invitations
        $this->createInvitations();

        // Step 30: Create Finance Categories and Expenses
        $this->createExpenseCategories();
        $this->createExpenses();

        // Step 29: Create FCM Tokens
        $this->createFcmTokens();

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║              ✅ Seeding Complete!                        ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        $this->printSummary();
    }

    private function createGovernoratesAndCities(): void
    {
        echo "📍 Creating Governorates and Cities...\n";

        $governoratesData = [
            [
                'name_ar' => 'دمشق',
                'name_en' => 'Damascus',
                'cities' => ['دمشق', 'المزة', 'برزة', 'القابون', 'جوبر']
            ],
            [
                'name_ar' => 'ريف دمشق',
                'name_en' => 'Rif Damascus',
                'cities' => ['دوما', 'الزبداني', 'التل', 'قطنا', 'النبك']
            ],
            [
                'name_ar' => 'حلب',
                'name_en' => 'Aleppo',
                'cities' => ['حلب', 'عفرين', 'الأعزاز', 'منبج', 'جرابلس']
            ],
            [
                'name_ar' => 'حمص',
                'name_en' => 'Homs',
                'cities' => ['حمص', 'طرطوس', 'اللاذقية', 'حماة', 'سلمية']
            ],
            [
                'name_ar' => 'اللاذقية',
                'name_en' => 'Latakia',
                'cities' => ['اللاذقية', 'جبلة', 'الحفة', 'القرداحة', 'بانياس']
            ],
        ];

        foreach ($governoratesData as $govData) {
            $governorate = Governorate::firstOrCreate(
                ['name_ar' => $govData['name_ar']],
                ['name_en' => $govData['name_en']]
            );

            foreach ($govData['cities'] as $cityName) {
                City::firstOrCreate(
                    [
                        'governorate_id' => $governorate->id,
                        'name_ar' => $cityName
                    ],
                    ['name_en' => $cityName]
                );
            }

            echo "  ✓ Governorate: {$governorate->name_ar} ({$governorate->name_en})\n";
        }

        $this->command->info("   Created " . Governorate::count() . " governorates and " . City::count() . " cities\n");
    }

    private function createDistricts(): void
    {
        echo "📍 Creating Districts...\n";

        $districtsData = [
            ['name_ar' => 'المزة', 'name_en' => 'Mazzeh'],
            ['name_ar' => 'المالكي', 'name_en' => 'Maliki'],
            ['name_ar' => 'أبو رمانة', 'name_en' => 'Abu Rummaneh'],
            ['name_ar' => 'الشعلان', 'name_en' => 'Shalan'],
            ['name_ar' => 'المهاجرين', 'name_en' => 'Muhajireen'],
            ['name_ar' => 'القصاع', 'name_en' => 'Qassaa'],
            ['name_ar' => 'الميدان', 'name_en' => 'Midan'],
            ['name_ar' => 'باب توما', 'name_en' => 'Bab Touma'],
            ['name_ar' => 'الحميدية', 'name_en' => 'Hamidiyeh'],
            ['name_ar' => 'ساروجا', 'name_en' => 'Sarouja'],
        ];

        $damascusGov = Governorate::where('name_ar', 'دمشق')->first();

        if ($damascusGov) {
            foreach ($districtsData as $districtData) {
                District::firstOrCreate(
                    [
                        'governorate_id' => $damascusGov->id,
                        'name_ar' => $districtData['name_ar']
                    ],
                    ['name_en' => $districtData['name_en']]
                );
            }
            $this->command->info("   Created " . District::count() . " districts\n");
        }
    }

    private function createSpecializations(): void
    {
        echo "🩺 Creating Specializations...\n";

        $specializations = [
            ['name_ar' => 'طب عام', 'name_en' => 'General Medicine', 'icon' => 'stethoscope'],
            ['name_ar' => 'طب الأطفال', 'name_en' => 'Pediatrics', 'icon' => 'baby'],
            ['name_ar' => 'طب القلب', 'name_en' => 'Cardiology', 'icon' => 'heart'],
            ['name_ar' => 'طب الأسنان', 'name_en' => 'Dentistry', 'icon' => 'tooth'],
            ['name_ar' => 'الجراحة العامة', 'name_en' => 'General Surgery', 'icon' => 'surgery'],
            ['name_ar' => 'طب العيون', 'name_en' => 'Ophthalmology', 'icon' => 'eye'],
            ['name_ar' => 'الأمراض الجلدية', 'name_en' => 'Dermatology', 'icon' => 'skin'],
            ['name_ar' => 'طب النساء والولادة', 'name_en' => 'Obstetrics and Gynecology', 'icon' => 'pregnant'],
            ['name_ar' => 'جراحة العظام', 'name_en' => 'Orthopedics', 'icon' => 'bone'],
            ['name_ar' => 'الطب النفسي', 'name_en' => 'Psychiatry', 'icon' => 'brain'],
            ['name_ar' => 'الأنف والأذن والحنجرة', 'name_en' => 'ENT', 'icon' => 'ear'],
            ['name_ar' => 'المسالك البولية', 'name_en' => 'Urology', 'icon' => 'kidney'],
            ['name_ar' => 'الجراحة العصبية', 'name_en' => 'Neurosurgery', 'icon' => 'neuro'],
            ['name_ar' => 'الغدد الصماء', 'name_en' => 'Endocrinology', 'icon' => 'hormone'],
            ['name_ar' => 'الجهاز الهضمي', 'name_en' => 'Gastroenterology', 'icon' => 'stomach'],
            ['name_ar' => 'العلاج الطبيعي', 'name_en' => 'Physiotherapy', 'icon' => 'therapy'],
            ['name_ar' => 'التغذية العلاجية', 'name_en' => 'Clinical Nutrition', 'icon' => 'nutrition'],
            ['name_ar' => 'الحساسية والمناعة', 'name_en' => 'Allergy and Immunology', 'icon' => 'immune'],
        ];

        foreach ($specializations as $specData) {
            Specialization::firstOrCreate(
                ['name_ar' => $specData['name_ar']],
                $specData
            );
        }

        $this->command->info("   Created " . Specialization::count() . " specializations\n");
    }

    private function createMedicalSpecializations(): void
    {
        echo "🏥 Creating Medical Specializations...\n";

        $medicalSpecs = [
            ['name' => 'طب عام', 'image_url' => ''],
            ['name' => 'طب باطني', 'image_url' => ''],
            ['name' => 'جراحة عامة', 'image_url' => ''],
            ['name' => 'طب أطفال', 'image_url' => ''],
            ['name' => 'نسائية وتوليد', 'image_url' => ''],
        ];

        foreach ($medicalSpecs as $spec) {
            MedicalSpecialization::firstOrCreate(
                ['name' => $spec['name']],
                $spec
            );
        }

        $this->command->info("   Created " . MedicalSpecialization::count() . " medical specializations\n");
    }

    private function createMethods(): void
    {
        echo "💳 Creating Payment Methods...\n";

        $methods = [
            ['name' => 'نقدي'],
            ['name' => 'بطاقة ائتمان'],
            ['name' => 'تحويل بنكي'],
            ['name' => 'محفظة إلكترونية'],
            ['name' => 'تأمين صحي'],
        ];

        foreach ($methods as $method) {
            Method::firstOrCreate(
                ['name' => $method['name']]
            );
        }

        $this->command->info("   Created " . Method::count() . " payment methods\n");
    }

    private function createUsers(): void
    {
        echo "👥 Creating Users...\n";

        // Create Patient Users
        $patientUsers = [
            ['first_name' => 'محمد', 'last_name' => 'الأحمد', 'email' => 'patient1@test.com', 'phone' => '963911111111', 'gender' => 'male', 'birth_date' => '1990-05-15'],
            ['first_name' => 'فاطمة', 'last_name' => 'الحسين', 'email' => 'patient2@test.com', 'phone' => '+963911111112', 'gender' => 'female', 'birth_date' => '1985-08-20'],
            ['first_name' => 'أحمد', 'last_name' => 'العلي', 'email' => 'patient3@test.com', 'phone' => '+963911111113', 'gender' => 'male', 'birth_date' => '1992-03-10'],
            ['first_name' => 'نور', 'last_name' => 'الدين', 'email' => 'patient4@test.com', 'phone' => '+963911111114', 'gender' => 'female', 'birth_date' => '1988-12-05'],
            ['first_name' => 'خالد', 'last_name' => 'المحمد', 'email' => 'patient5@test.com', 'phone' => '+963911111115', 'gender' => 'male', 'birth_date' => '1995-07-25'],
            ['first_name' => 'ليلى', 'last_name' => 'السعيد', 'email' => 'patient6@test.com', 'phone' => '+963911111116', 'gender' => 'female', 'birth_date' => '1993-02-14'],
            ['first_name' => 'عمر', 'last_name' => 'الحسن', 'email' => 'patient7@test.com', 'phone' => '+963911111117', 'gender' => 'male', 'birth_date' => '1987-11-30'],
            ['first_name' => 'سارة', 'last_name' => 'الرشيد', 'email' => 'patient8@test.com', 'phone' => '+963911111118', 'gender' => 'female', 'birth_date' => '1991-06-18'],
            ['first_name' => 'ياسر', 'last_name' => 'القاسم', 'email' => 'patient9@test.com', 'phone' => '+963911111119', 'gender' => 'male', 'birth_date' => '1989-09-22'],
            ['first_name' => 'هدى', 'last_name' => 'الزهر', 'email' => 'patient10@test.com', 'phone' => '+963911111120', 'gender' => 'female', 'birth_date' => '1994-04-08'],
        ];

        foreach ($patientUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['password' => 'Patient@123', 'status' => 'approved'])
            );

            Patient::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'governorate_id' => 1,
                    'city_id' => 1,
                    'occupation' => fake()->jobTitle(),
                    'loyalty_points_balance' => rand(0, 500),
                ]
            );
        }

        echo "  ✓ Created " . Patient::count() . " patients\n";

        // Create Secretary Users
        $secretaryUsers = [
            ['first_name' => 'رنا', 'last_name' => 'العبدي', 'email' => 'sec1@test.com', 'phone' => '+963922222221', 'gender' => 'female'],
            ['first_name' => 'ماهر', 'last_name' => 'التميمي', 'email' => 'sec2@test.com', 'phone' => '+963922222222', 'gender' => 'male'],
            ['first_name' => 'غادة', 'last_name' => 'المنصور', 'email' => 'sec3@test.com', 'phone' => '+963922222223', 'gender' => 'female'],
        ];

        foreach ($secretaryUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['password' => 'Secretary@123', 'status' => 'approved'])
            );
        }

        echo "  ✓ Created " . User::whereHas('secretary')->count() . " secretary users\n";
        $this->command->info("   Total users created: " . User::count() . "\n");
    }

    private function createDoctors(): void
    {
        echo "👨‍⚕️ Creating Doctors...\n";

        $doctorsData = [
            [
                'user' => ['first_name' => 'د. أحمد', 'last_name' => 'الخطيب', 'email' => 'doctor1@test.com', 'phone' => '+963933333331', 'gender' => 'male'],
                'doctor' => ['username' => 'dr_ahmad_khatib', 'license_number' => 'MED-2010-001', 'practicing_profession_date' => 2010, 'bio' => 'طبيب عام ذو خبرة واسعة في علاج الأمراض المزمنة', 'has_secretary_service' => true],
                'specialization_ids' => [1],
            ],
            [
                'user' => ['first_name' => 'د. سارة', 'last_name' => 'الشماع', 'email' => 'doctor2@test.com', 'phone' => '+963933333332', 'gender' => 'female'],
                'doctor' => ['username' => 'dr_sara_shammaa', 'license_number' => 'PED-2012-002', 'practicing_profession_date' => 2012, 'bio' => 'أخصائية طب الأطفال وحديثي الولادة', 'has_secretary_service' => true],
                'specialization_ids' => [2],
            ],
            [
                'user' => ['first_name' => 'د. محمود', 'last_name' => 'الرفاعي', 'email' => 'doctor3@test.com', 'phone' => '+963933333333', 'gender' => 'male'],
                'doctor' => ['username' => 'dr_mahmoud_rifai', 'license_number' => 'CARD-2008-003', 'practicing_profession_date' => 2008, 'bio' => 'استشاري أمراض القلب والشرايين', 'has_secretary_service' => true],
                'specialization_ids' => [3],
            ],
            [
                'user' => ['first_name' => 'د. ريم', 'last_name' => 'الكيال', 'email' => 'doctor4@test.com', 'phone' => '+963933333334', 'gender' => 'female'],
                'doctor' => ['username' => 'dr_reem_kayyal', 'license_number' => 'DENT-2015-004', 'practicing_profession_date' => 2015, 'bio' => 'طبيبة أسنان متخصصة في التجميل وزراعة الأسنان', 'has_secretary_service' => false],
                'specialization_ids' => [4],
            ],
            [
                'user' => ['first_name' => 'د. إياد', 'last_name' => 'الشامي', 'email' => 'doctor5@test.com', 'phone' => '+963933333335', 'gender' => 'male'],
                'doctor' => ['username' => 'dr_iyad_shami', 'license_number' => 'SURG-2006-005', 'practicing_profession_date' => 2006, 'bio' => 'جراح عام متخصص في الجراحة التنظيرية', 'has_secretary_service' => true],
                'specialization_ids' => [5],
            ],
            [
                'user' => ['first_name' => 'د. لمى', 'last_name' => 'النوري', 'email' => 'doctor6@test.com', 'phone' => '+963933333336', 'gender' => 'female'],
                'doctor' => ['username' => 'dr_lama_nouri', 'license_number' => 'EYE-2014-006', 'practicing_profession_date' => 2014, 'bio' => 'أخصائية أمراض العيون والجراحة الانكسارية', 'has_secretary_service' => false],
                'specialization_ids' => [6],
            ],
            [
                'user' => ['first_name' => 'د. بلال', 'last_name' => 'العظم', 'email' => 'doctor7@test.com', 'phone' => '+963933333337', 'gender' => 'male'],
                'doctor' => ['username' => 'dr_bilal_azm', 'license_number' => 'DERM-2011-007', 'practicing_profession_date' => 2011, 'bio' => 'أخصائي الأمراض الجلدية والتجميل', 'has_secretary_service' => true],
                'specialization_ids' => [7],
            ],
            [
                'user' => ['first_name' => 'د. منى', 'last_name' => 'الفاروق', 'email' => 'doctor8@test.com', 'phone' => '+963933333338', 'gender' => 'female'],
                'doctor' => ['username' => 'dr_mona_farouq', 'license_number' => 'OBG-2009-008', 'practicing_profession_date' => 2009, 'bio' => 'استشارية النساء والولادة والعقم', 'has_secretary_service' => true],
                'specialization_ids' => [8],
            ],
            [
                'user' => ['first_name' => 'د. هشام', 'last_name' => 'القدسي', 'email' => 'doctor9@test.com', 'phone' => '+963933333339', 'gender' => 'male'],
                'doctor' => ['username' => 'dr_hisham_qudsi', 'license_number' => 'ORTH-2007-009', 'practicing_profession_date' => 2007, 'bio' => 'جراح عظام ومفاصل', 'has_secretary_service' => true],
                'specialization_ids' => [9],
            ],
            [
                'user' => ['first_name' => 'د. نادين', 'last_name' => 'الخوري', 'email' => 'doctor10@test.com', 'phone' => '+963933333340', 'gender' => 'female'],
                'doctor' => ['username' => 'dr_nadine_khoury', 'license_number' => 'PSY-2013-010', 'practicing_profession_date' => 2013, 'bio' => 'أخصائية الطب النفسي والعلاج النفسي', 'has_secretary_service' => false],
                'specialization_ids' => [10],
            ],
        ];

        foreach ($doctorsData as $doctorData) {
            $user = User::firstOrCreate(
                ['email' => $doctorData['user']['email']],
                array_merge($doctorData['user'], ['password' => 'Doctor@123', 'status' => 'approved'])
            );

            $doctor = Doctor::firstOrCreate(
                ['user_id' => $user->id],
                array_merge($doctorData['doctor'], [
                    'governorate_id' => 1,
                    'status' => 'approved',
                    'phone_verified' => true,
                ])
            );

            // Attach specializations
            $doctor->specializations()->sync($doctorData['specialization_ids']);

            // Create certifications
            Certification::firstOrCreate(
                ['doctor_id' => $doctor->id, 'name' => 'Board Certification in ' . Specialization::find($doctorData['specialization_ids'][0])->name_en],
                [
                    'image_url' => 'certifications/cert_' . $doctor->id . '.jpg',
                ]
            );

            echo "  ✓ Created {$doctorData['user']['first_name']} {$doctorData['user']['last_name']}\n";
        }

        $this->command->info("   Total doctors created: " . Doctor::count() . "\n");
    }

    private function createClinics(): void
    {
        echo "🏥 Creating Clinics...\n";

        $clinicsData = [
            [
                'clinic_name' => 'عيادة الشفاء الطبية',
                'username' => 'clinic_shifa',
                'email' => 'shifa@clinic.test',
                'phone' => '+963944444441',
                'description' => 'عيادة طبية شاملة تقدم خدمات عالية الجودة',
                'specialization_id' => 1,
                'consultation_fee' => 50000,
            ],
            [
                'clinic_name' => 'عيادة النور للأسنان',
                'username' => 'clinic_noor',
                'email' => 'noor@clinic.test',
                'phone' => '+963944444442',
                'description' => 'عيادة متخصصة في طب الأسنان والتجميل',
                'specialization_id' => 4,
                'consultation_fee' => 40000,
            ],
            [
                'clinic_name' => 'عيادة القلب والأوعية',
                'username' => 'clinic_heart',
                'email' => 'heart@clinic.test',
                'phone' => '+963944444443',
                'description' => 'مركز متخصص في أمراض القلب',
                'specialization_id' => 3,
                'consultation_fee' => 75000,
            ],
            [
                'clinic_name' => 'عيادة الأطفال السعيدة',
                'username' => 'clinic_kids',
                'email' => 'kids@clinic.test',
                'phone' => '+963944444444',
                'description' => 'عيادة صديقة للأطفال متخصصة في طب الأطفال',
                'specialization_id' => 2,
                'consultation_fee' => 45000,
            ],
            [
                'clinic_name' => 'عيادة الجلدية والتجميل',
                'username' => 'clinic_skin',
                'email' => 'skin@clinic.test',
                'phone' => '+963944444445',
                'description' => 'عيادة متخصصة في الأمراض الجلدية والتجميل',
                'specialization_id' => 7,
                'consultation_fee' => 60000,
            ],
        ];

        foreach ($clinicsData as $clinicData) {
            $clinic = Clinic::firstOrCreate(
                ['username' => $clinicData['username']],
                array_merge($clinicData, [
                    'password' => 'Clinic@123',
                    'governorate_id' => 1,
                    'city_id' => 1,
                    'district_id' => 1,
                    'address' => 'شارع بغداد',
                    'detailed_address' => 'مجمع النور الطبي، الطابق الثالث',
                    'floor' => 3,
                    'room_number' => '301',
                    'latitude' => 33.5138 + (rand(-100, 100) / 10000),
                    'longitude' => 36.2765 + (rand(-100, 100) / 10000),
                    'status' => 'approved',
                    'working_hours' => [
                        'sunday' => ['start' => '09:00', 'end' => '17:00'],
                        'monday' => ['start' => '09:00', 'end' => '17:00'],
                        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                        'thursday' => ['start' => '09:00', 'end' => '14:00'],
                    ],
                ])
            );

            echo "  ✓ Created {$clinic->clinic_name}\n";
        }

        $this->command->info("   Total clinics created: " . Clinic::count() . "\n");
    }

    /**
     * Attach each clinic to a medical center (for center reviews & finance flows).
     */
    private function attachClinicsToMedicalCenters(): void
    {
        echo "🏥🔗 Attaching Clinics to Medical Centers...\n";

        $clinics = Clinic::all();
        $centers = MedicalCenter::all();

        if ($clinics->isEmpty() || $centers->isEmpty()) {
            $this->command->warn("   Skipped attaching clinics to centers (no data).");
            return;
        }

        $centerIds = $centers->pluck('id')->all();

        foreach ($clinics as $index => $clinic) {
            $centerId = $centerIds[$index % count($centerIds)];
            $clinic->medical_center_id = $centerId;
            $clinic->save();

            echo "  ✓ Attached Clinic ID: {$clinic->id} to Center ID: {$centerId}\n";
        }

        $this->command->info("   Attached " . $clinics->count() . " clinics to medical centers.\n");
    }

    private function createMedicalCenters(): void
    {
        echo "🏨 Creating Medical Centers...\n";

        $centersData = [
            [
                'name' => 'مركز دمشق الطبي',
                'username' => 'center_damascus',
                'email' => 'damascus@center.test',
                'phone' => '+963955555551',
                'description' => 'مركز طبي متكامل يضم عدة تخصصات',
            ],
            [
                'name' => 'مركز الياسمين الصحي',
                'username' => 'center_yasmin',
                'email' => 'yasmin@center.test',
                'phone' => '+963955555552',
                'description' => 'مركز صحي شامل',
            ],
            [
                'name' => 'مركز الشام الطبي',
                'username' => 'center_sham',
                'email' => 'sham@center.test',
                'phone' => '+963955555553',
                'description' => 'مركز متخصص في الجراحة',
            ],
        ];

        foreach ($centersData as $centerData) {
            $user = User::firstOrCreate(
                ['email' => $centerData['email']],
                [
                    'first_name' => explode(' ', $centerData['name'])[0],
                    'last_name' => 'الطبي',
                    'phone' => $centerData['phone'],
                    'password' => 'Center@123',
                    'gender' => 'male',
                    'status' => 'approved',
                ]
            );

            $center = MedicalCenter::firstOrCreate(
                ['username' => $centerData['username']],
                [
                    'user_id' => $user->id,
                    'governorate_id' => 1,
                    'city_id' => 1,
                    'name' => $centerData['name'],
                    'password' => 'Center@123',
                    'address_details' => 'مجمع طبي كبير',
                    'latitude' => 33.5138 + (rand(-100, 100) / 10000),
                    'longitude' => 36.2765 + (rand(-100, 100) / 10000),
                    'description' => $centerData['description'],
                    'status' => 'approved',
                    'clinic_count' => rand(5, 15),
                    'working_hours' => [
                        'sunday' => ['start' => '08:00', 'end' => '20:00'],
                        'monday' => ['start' => '08:00', 'end' => '20:00'],
                        'tuesday' => ['start' => '08:00', 'end' => '20:00'],
                        'wednesday' => ['start' => '08:00', 'end' => '20:00'],
                        'thursday' => ['start' => '08:00', 'end' => '16:00'],
                    ],
                ]
            );

            echo "  ✓ Created {$center->name}\n";
        }

        $this->command->info("   Total medical centers created: " . MedicalCenter::count() . "\n");
    }

    private function createClinicDoctorRelationships(): void
    {
        echo "🔗 Creating Clinic-Doctor Relationships...\n";

        $clinics = Clinic::all();
        $doctors = Doctor::all();
        $methods = Method::all();
        $defaultMethod = $methods->first()?->id;

        foreach ($clinics as $clinic) {
            // Assign 1-3 doctors to each clinic
            $assignedDoctors = $doctors->random(rand(1, 3));

            foreach ($assignedDoctors as $doctor) {
                ClinicDoctor::firstOrCreate(
                    ['clinic_id' => $clinic->id, 'doctor_id' => $doctor->id],
                    [
                        'is_primary' => $assignedDoctors->first()->id === $doctor->id,
                        'method_id' => $defaultMethod,
                        'appointment_period' => 30,
                    ]
                );
            }

            echo "  ✓ Assigned doctors to {$clinic->clinic_name}\n";
        }

        $this->command->info("   Total clinic-doctor relationships: " . ClinicDoctor::count() . "\n");
    }

    private function createSecretaries(): void
    {
        echo "👩‍💼 Creating Secretaries...\n";

        $secretaryUsers = User::whereHas('secretary', function ($q) {
            $q->whereNull('entity_id');
        })->get();

        $clinics = Clinic::all();
        $medicalCenters = MedicalCenter::all();

        $i = 0;
        foreach ($secretaryUsers as $user) {
            $entity = $i % 2 === 0 ? $clinics->random() : $medicalCenters->random();

            $secretary = Secretary::where('user_id', $user->id)->first();

            if (!$secretary) {
                Secretary::create([
                    'user_id' => $user->id,
                    'username' => 'sec_' . $user->first_name,
                    'entity_type' => get_class($entity),
                    'entity_id' => $entity->id,
                    'status' => 'active',
                ]);
            }

            echo "  ✓ Assigned secretary to " . get_class($entity) . " ID: {$entity->id}\n";
            $i++;
        }

        $this->command->info("   Total secretaries created: " . Secretary::count() . "\n");
    }

    private function createClinicServices(): void
    {
        echo "💊 Creating Clinic Services...\n";

        $servicesData = [
            ['name' => 'كشف طبي عام', 'price' => 50000],
            ['name' => 'حشو أسنان', 'price' => 80000],
            ['name' => 'تنظيف أسنان', 'price' => 60000],
            ['name' => 'تخطيط قلب', 'price' => 40000],
            ['name' => 'إزالة جير الأسنان', 'price' => 70000],
            ['name' => 'علاج عصب', 'price' => 150000],
            ['name' => 'تبييض الأسنان', 'price' => 120000],
            ['name' => 'استشارة تغذية', 'price' => 45000],
        ];

        $clinics = Clinic::all();

        foreach ($clinics as $clinic) {
            $services = collect($servicesData)->random(rand(2, 4));

            foreach ($services as $serviceData) {
                ClinicService::firstOrCreate(
                    [
                        'clinic_id' => $clinic->id,
                        'name' => $serviceData['name'],
                    ],
                    $serviceData
                );
            }

            echo "  ✓ Added services to {$clinic->clinic_name}\n";
        }

        $this->command->info("   Total clinic services created: " . ClinicService::count() . "\n");
    }

    private function createClinicGalleryImages(): void
    {
        echo "🖼️ Creating Clinic Gallery Images...\n";

        $clinics = Clinic::all();

        foreach ($clinics as $clinic) {
            for ($i = 0; $i < rand(2, 5); $i++) {
                ClinicGalleryImage::firstOrCreate(
                    [
                        'clinic_id' => $clinic->id,
                        'image_path' => "gallery/clinic_{$clinic->id}_{$i}.jpg",
                    ]
                );
            }

            echo "  ✓ Added gallery images to {$clinic->clinic_name}\n";
        }

        $this->command->info("   Total clinic gallery images created: " . ClinicGalleryImage::count() . "\n");
    }

    private function createMedicalCenterServices(): void
    {
        echo "💊 Creating Medical Center Services...\n";

        $servicesData = [
            ['name' => 'أشعة سينية', 'price' => 30000],
            ['name' => 'تحاليل دم', 'price' => 50000],
            ['name' => 'إسعافات أولية', 'price' => 25000],
            ['name' => 'علاج فيزيائي', 'price' => 80000],
            ['name' => 'تصوير إيكو', 'price' => 100000],
        ];

        $centers = MedicalCenter::all();

        foreach ($centers as $center) {
            foreach ($servicesData as $serviceData) {
                MedicalCenterService::firstOrCreate(
                    [
                        'medical_center_id' => $center->id,
                        'name' => $serviceData['name'],
                    ],
                    $serviceData
                );
            }

            echo "  ✓ Added services to {$center->name}\n";
        }

        $this->command->info("   Total medical center services created: " . MedicalCenterService::count() . "\n");
    }

    private function createMedicalCenterGalleryImages(): void
    {
        echo "🖼️ Creating Medical Center Gallery Images...\n";

        $centers = MedicalCenter::all();

        foreach ($centers as $center) {
            for ($i = 0; $i < rand(3, 6); $i++) {
                MedicalCenterGalleryImage::firstOrCreate(
                    [
                        'medical_center_id' => $center->id,
                        'image_path' => "gallery/center_{$center->id}_{$i}.jpg",
                    ]
                );
            }

            echo "  ✓ Added gallery images to {$center->name}\n";
        }

        $this->command->info("   Total medical center gallery images created: " . MedicalCenterGalleryImage::count() . "\n");
    }

    private function createDoctorClinicSchedules(): void
    {
        echo "📅 Creating Doctor Clinic Schedules...\n";

        $clinicDoctors = ClinicDoctor::all();

        // ISO day numbers: 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday, 7=Sunday
        $daysOfWeek = [1, 2, 3, 4, 5]; // Monday to Friday

        foreach ($clinicDoctors as $clinicDoctor) {
            foreach ($daysOfWeek as $dayOfWeek) {
                DoctorClinicSchedule::firstOrCreate(
                    [
                        'doctor_id' => $clinicDoctor->doctor_id,
                        'clinic_id' => $clinicDoctor->clinic_id,
                        'day_of_week' => $dayOfWeek,
                    ],
                    [
                        'start_time' => '09:00',
                        'end_time' => '17:00',
                        'appointment_duration' => 30,
                        'is_active' => true,
                        'effective_from' => now()->format('Y-m-d'),
                        'version' => 1,
                    ]
                );
            }

            echo "  ✓ Created schedule for Doctor ID: {$clinicDoctor->doctor_id} at Clinic ID: {$clinicDoctor->clinic_id}\n";
        }

        $this->command->info("   Total doctor clinic schedules created: " . DoctorClinicSchedule::count() . "\n");
    }

    private function createScheduleSlots(): void
    {
        echo "🕐 Creating Schedule Slots...\n";

        $schedules = DoctorClinicSchedule::all();

        foreach ($schedules as $schedule) {
            // Create slots for the next 7 days
            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                $date = now()->addDays($dayOffset);
                $dayOfWeekIso = $date->dayOfWeekIso; // 1=Monday, 7=Sunday

                // Match schedule day_of_week with date's day of week
                if ($dayOfWeekIso === $schedule->day_of_week) {
                    $startTime = \Carbon\Carbon::parse($schedule->start_time);
                    $endTime = \Carbon\Carbon::parse($schedule->end_time);
                    $breaks = $schedule->breaks ?? [];

                    $currentTime = $startTime;
                    $slotNumber = 1;

                    while ($currentTime < $endTime) {
                        // Check if current time is within any break period
                        $isBreakTime = false;
                        foreach ($breaks as $break) {
                            $breakStart = \Carbon\Carbon::parse($break['start']);
                            $breakEnd = \Carbon\Carbon::parse($break['end']);
                            if ($currentTime >= $breakStart && $currentTime < $breakEnd) {
                                $isBreakTime = true;
                                $currentTime = $breakEnd;
                                break;
                            }
                        }

                        if ($isBreakTime) {
                            continue;
                        }

                        $slotEndTime = $currentTime->copy()->addMinutes($schedule->appointment_duration);

                        if ($slotEndTime <= $endTime) {
                            ScheduleSlot::firstOrCreate(
                                [
                                    'schedule_id' => $schedule->id,
                                    'date' => $date->format('Y-m-d'),
                                    'start_time' => $currentTime->format('H:i'),
                                ],
                                [
                                    'doctor_id' => $schedule->doctor_id,
                                    'clinic_id' => $schedule->clinic_id,
                                    'day_of_week' => $schedule->day_of_week,
                                    'end_time' => $slotEndTime->format('H:i'),
                                    'slot_type' => 'open',
                                    'creation_method' => 'auto',
                                    'status' => 'available',
                                ]
                            );
                        }

                        $currentTime = $slotEndTime;
                        $slotNumber++;
                    }
                }
            }

            echo "  ✓ Created slots for schedule ID: {$schedule->id}\n";
        }

        $this->command->info("   Total schedule slots created: " . ScheduleSlot::count() . "\n");
    }

    private function createAppointments(): void
    {
        echo "📋 Creating Appointments...\n";

        $patients = Patient::all();
        $slots = ScheduleSlot::where('status', 'available')->get();

        $appointmentStatuses = ['booked', 'confirmed', 'completed', 'pending_approval', 'cancelled'];
        $paymentStatuses = ['unpaid', 'partial_paid', 'full_paid', 'refunded'];
        $sources = ['patient_app', 'doctor_app', 'secretary_panel', 'website'];

        foreach ($patients->take(8) as $patient) {
            $slot = $slots->random();

            $doctor = Doctor::find($slot->schedule->doctor_id);
            $clinic = Clinic::find($slot->schedule->clinic_id);

            $status = $appointmentStatuses[array_rand($appointmentStatuses)];
            // Simple mapping for seeding:
            // - completed -> full_paid
            // - cancelled -> refunded
            // - others -> unpaid
            $paymentStatus = $status === 'completed'
                ? 'full_paid'
                : ($status === 'cancelled' ? 'refunded' : 'unpaid');

            $appointment = Appointment::firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'schedule_slot_id' => $slot->id,
                ],
                [
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $clinic->id,
                    'schedule_id' => $slot->schedule_id,
                    'date' => $slot->date,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'status' => $status,
                    'type' => 'consultation',
                    'payment_status' => $paymentStatus,
                    'payment_method' => 'cash',
                    'price_at_booking' => $clinic->consultation_fee,
                    'source' => $sources[array_rand($sources)],
                ]
            );

            // Update slot status
            if (in_array($status, ['booked', 'confirmed', 'completed'])) {
                $slot->update(['status' => 'booked']);
            }

            echo "  ✓ Created appointment for Patient ID: {$patient->id} on {$slot->date}\n";
        }

        $this->command->info("   Total appointments created: " . Appointment::count() . "\n");
    }

    private function createMedicalFiles(): void
    {
        echo "📁 Creating Medical Files...\n";

        $patients = Patient::all();
        $bloodTypes = ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-'];

        foreach ($patients as $patient) {
            MedicalFile::firstOrCreate(
                ['patient_id' => $patient->id],
                [
                    'blood_type' => $bloodTypes[array_rand($bloodTypes)],
                    'past_medical_history' => 'No significant medical history',
                ]
            );

            echo "  ✓ Created medical file for Patient ID: {$patient->id}\n";
        }

        $this->command->info("   Total medical files created: " . MedicalFile::count() . "\n");
    }

    private function createVisitRecords(): void
    {
        echo "📝 Creating Visit Records...\n";

        $completedAppointments = Appointment::where('status', 'completed')->get();

        foreach ($completedAppointments as $appointment) {
            $medicalFile = MedicalFile::where('patient_id', $appointment->patient_id)->first();

            if ($medicalFile) {
                VisitRecord::firstOrCreate(
                    ['appointment_id' => $appointment->id],
                    [
                        'medical_file_id' => $medicalFile->id,
                        'visit_date' => $appointment->date,
                        'diagnosis' => 'Preliminary diagnosis',
                        'notes' => 'Visit notes',
                    ]
                );

                echo "  ✓ Created visit record for Appointment ID: {$appointment->id}\n";
            }
        }

        $this->command->info("   Total visit records created: " . VisitRecord::count() . "\n");
    }

    private function createPrescriptions(): void
    {
        echo "💊 Creating Prescriptions...\n";

        $visitRecords = VisitRecord::all();

        foreach ($visitRecords as $visit) {
            Prescription::firstOrCreate(
                ['visit_record_id' => $visit->id],
                [
                    'medication_name' => 'Paracetamol 500mg',
                    'dosage' => '1 tablet every 6 hours',
                    'instructions' => 'Take after food',
                ]
            );

            echo "  ✓ Created prescription for Visit ID: {$visit->id}\n";
        }

        $this->command->info("   Total prescriptions created: " . Prescription::count() . "\n");
    }

    private function createLabResults(): void
    {
        echo "🔬 Creating Lab Results...\n";

        $visitRecords = VisitRecord::all();

        foreach ($visitRecords->take(3) as $visit) {
            LabResult::firstOrCreate(
                ['visit_record_id' => $visit->id],
                [
                    'test_type' => 'blood',
                    'result_data' => json_encode(['CBC' => 'Normal']),
                    'attachment_url' => 'lab_results/test_' . $visit->id . '.pdf',
                ]
            );

            echo "  ✓ Created lab result for Visit ID: {$visit->id}\n";
        }

        $this->command->info("   Total lab results created: " . LabResult::count() . "\n");
    }

    private function createReviews(): void
    {
        echo "⭐ Creating Reviews...\n";

        $completedAppointments = Appointment::where('status', 'completed')->get();
        $patients = Patient::all();

        foreach ($completedAppointments->take(5) as $appointment) {
            $patient = Patient::find($appointment->patient_id);

            Review::firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'clinic_id' => $appointment->clinic_id,
                ],
                [
                    'doctor_id' => $appointment->doctor_id,
                    'rating' => rand(4, 5),
                    'comment' => 'Very good experience, professional doctor',
                ]
            );

            echo "  ✓ Created review for Clinic ID: {$appointment->clinic_id}\n";
        }

        $this->command->info("   Total reviews created: " . Review::count() . "\n");
    }

    /**
     * Create default expense categories for each clinic.
     */
    private function createExpenseCategories(): void
    {
        echo "📂 Creating Expense Categories...\n";

        $clinics = Clinic::all();

        if ($clinics->isEmpty()) {
            $this->command->warn("   Skipped creating expense categories (no clinics).");
            return;
        }

        $defaultCategories = [
            ['name' => 'مستلزمات طبية', 'icon_type' => 'box'],
            ['name' => 'إيجار وفواتير', 'icon_type' => 'home'],
            ['name' => 'صيانة', 'icon_type' => 'tool'],
            ['name' => 'رواتب الموظفين', 'icon_type' => 'users'],
        ];

        foreach ($clinics as $clinic) {
            foreach ($defaultCategories as $categoryData) {
                ExpenseCategory::firstOrCreate(
                    [
                        'clinic_id' => $clinic->id,
                        'name' => $categoryData['name'],
                    ],
                    [
                        'icon_type' => $categoryData['icon_type'],
                    ]
                );
            }

            echo "  ✓ Created expense categories for Clinic ID: {$clinic->id}\n";
        }

        $this->command->info("   Total expense categories created: " . ExpenseCategory::count() . "\n");
    }

    /**
     * Create sample expenses for each clinic across multiple months
     * to easily test finance summary and list APIs.
     */
    private function createExpenses(): void
    {
        echo "💰 Creating Expenses...\n";

        $clinics = Clinic::all();

        if ($clinics->isEmpty()) {
            $this->command->warn("   Skipped creating expenses (no clinics).");
            return;
        }

        $currentYear = (int) now()->year;
        $months = [1, 2, 3, 4, 5, 6]; // First half of the year for easier testing

        foreach ($clinics as $clinic) {
            $categories = ExpenseCategory::where('clinic_id', $clinic->id)->get();

            if ($categories->isEmpty()) {
                continue;
            }

            foreach ($months as $month) {
                // Create 3 expenses per month
                for ($i = 0; $i < 3; $i++) {
                    $category = $categories->random();

                    $date = \Carbon\Carbon::create($currentYear, $month, rand(1, 25))->toDateString();

                    $title = match ($category->name) {
                        'مستلزمات طبية' => 'شراء مستلزمات طبية أساسية',
                        'إيجار وفواتير' => 'دفع إيجار العيادة والفواتير',
                        'صيانة' => 'صيانة الأجهزة الطبية',
                        'رواتب الموظفين' => 'دفع رواتب الطاقم الطبي والإداري',
                        default => 'مصروف عام للعيادة',
                    };

                    $amount = match ($category->name) {
                        'مستلزمات طبية' => rand(30000, 150000),
                        'إيجار وفواتير' => rand(200000, 500000),
                        'صيانة' => rand(50000, 200000),
                        'رواتب الموظفين' => rand(300000, 800000),
                        default => rand(20000, 100000),
                    };

                    Expense::firstOrCreate(
                        [
                            'clinic_id' => $clinic->id,
                            'category_id' => $category->id,
                            'title' => $title,
                            'date' => $date,
                        ],
                        [
                            'amount' => $amount,
                            'notes' => 'Seeded demo expense for finance API tests.',
                        ]
                    );
                }
            }

            echo "  ✓ Created expenses for Clinic ID: {$clinic->id}\n";
        }

        $this->command->info("   Total expenses created: " . Expense::count() . "\n");
    }

    private function createOffers(): void
    {
        echo "🎁 Creating Offers...\n";

        $clinics = Clinic::all();

        foreach ($clinics as $clinic) {
            Offer::firstOrCreate(
                [
                    'clinic_id' => $clinic->id,
                    'title' => 'Special Offer on Dental Cleaning',
                ],
                [
                    'description' => '20% discount on dental cleaning this month',
                    'discount_type' => 'percentage',
                    'discount_value' => 20,
                    'valid_until' => now()->addDays(30)->format('Y-m-d'),
                    'is_active' => true,
                ]
            );

            echo "  ✓ Created offer for {$clinic->clinic_name}\n";
        }

        $this->command->info("   Total offers created: " . Offer::count() . "\n");
    }

    private function createLoyaltyTransactions(): void
    {
        echo "💎 Creating Loyalty Transactions...\n";

        $patients = Patient::all();
        $completedAppointments = Appointment::where('status', 'completed')->get();

        foreach ($completedAppointments as $appointment) {
            $patient = Patient::find($appointment->patient_id);

            LoyaltyTransaction::firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'appointment_id' => $appointment->id,
                    'type' => 'earn',
                ],
                [
                    'points' => 10,
                    'description' => 'Reward points for completed visit',
                ]
            );

            echo "  ✓ Created loyalty transaction for Patient ID: {$patient->id}\n";
        }

        $this->command->info("   Total loyalty transactions created: " . LoyaltyTransaction::count() . "\n");
    }

    private function createSubscriptions(): void
    {
        echo "📦 Creating Subscriptions...\n";

        $plans = SubscriptionPlan::all();
        $clinics = Clinic::all();
        $centers = MedicalCenter::all();

        $clinicPlans = $plans->where('target_type', 'clinic');
        $centerPlans = $plans->where('target_type', 'medical_center');

        if ($clinicPlans->isEmpty()) {
            $this->command->warn("   No clinic subscription plans found. Skipping clinic subscriptions.");
        } else {
            foreach ($clinics as $clinic) {
                $plan = $clinicPlans->random();

                Subscription::firstOrCreate(
                    [
                        'subscribable_type' => Clinic::class,
                        'subscribable_id' => $clinic->id,
                        'subscription_plan_id' => $plan->id,
                    ],
                    [
                        'status' => 'active',
                        'starts_at' => now(),
                        'ends_at' => now()->addDays($plan->duration_days),
                        'notes' => null,
                    ]
                );

                echo "  ✓ Subscribed {$clinic->clinic_name} to {$plan->name}\n";
            }
        }

        if ($centerPlans->isEmpty()) {
            $this->command->warn("   No medical center subscription plans found. Skipping center subscriptions.");
        } else {
            foreach ($centers as $center) {
                $plan = $centerPlans->random();

                Subscription::firstOrCreate(
                    [
                        'subscribable_type' => MedicalCenter::class,
                        'subscribable_id' => $center->id,
                        'subscription_plan_id' => $plan->id,
                    ],
                    [
                        'status' => 'active',
                        'starts_at' => now(),
                        'ends_at' => now()->addDays($plan->duration_days),
                        'notes' => null,
                    ]
                );

                echo "  ✓ Subscribed {$center->name} to {$plan->name}\n";
            }
        }

        $this->command->info("   Total subscriptions created: " . Subscription::count() . "\n");
    }

    private function createWaitingLists(): void
    {
        echo "⏳ Creating Waiting Lists...\n";

        $patients = Patient::all();
        $clinics = Clinic::all();
        $doctors = Doctor::all();

        foreach ($clinics as $clinic) {
            $interestedPatients = $patients->random(rand(1, 3));

            foreach ($interestedPatients as $patient) {
                WaitingList::firstOrCreate(
                    [
                        'patient_id' => $patient->id,
                        'clinic_id' => $clinic->id,
                        'doctor_id' => $doctors->random()->id,
                        'target_date' => now()->addDays(rand(1, 7))->format('Y-m-d'),
                    ],
                    [
                        'status' => 'active',
                        'patient_note' => 'Patient interested in this clinic',
                    ]
                );
            }

            echo "  ✓ Created waiting list for {$clinic->clinic_name}\n";
        }

        $this->command->info("   Total waiting list entries created: " . WaitingList::count() . "\n");
    }

    private function createInvitations(): void
    {
        echo "📧 Creating Invitations...\n";

        $clinics = Clinic::all();
        $doctors = Doctor::all();

        foreach ($clinics as $clinic) {
            Invitation::firstOrCreate(
                [
                    'clinic_id' => $clinic->id,
                    'doctor_id' => $doctors->random()->id,
                ],
                [
                    'message' => 'Invitation to join the clinic',
                    'status' => 'pending',
                ]
            );

            echo "  ✓ Created invitation for {$clinic->clinic_name}\n";
        }

        $this->command->info("   Total invitations created: " . Invitation::count() . "\n");
    }

    private function createFcmTokens(): void
    {
        echo "📱 Creating FCM Tokens...\n";

        $users = User::take(5)->get();

        foreach ($users as $user) {
            UserFcmToken::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'fcm_token' => 'fcm_token_' . $user->id . '_' . bin2hex(random_bytes(16)),
                ]
            );

            echo "  ✓ Created FCM token for User ID: {$user->id}\n";
        }

        $this->command->info("   Total FCM tokens created: " . UserFcmToken::count() . "\n");
    }

    private function printSummary(): void
    {
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║                    📊 Data Summary                      ║\n";
        echo "╠══════════════════════════════════════════════════════════╣\n";
        echo sprintf("║  %-30s %10s                        ║\n", "Governorates:", Governorate::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Cities:", City::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Districts:", District::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Specializations:", Specialization::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Users:", User::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Patients:", Patient::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Doctors:", Doctor::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Clinics:", Clinic::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Medical Centers:", MedicalCenter::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Secretaries:", Secretary::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Appointments:", Appointment::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Medical Files:", MedicalFile::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Visit Records:", VisitRecord::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Prescriptions:", Prescription::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Lab Results:", LabResult::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Reviews:", Review::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Offers:", Offer::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Subscriptions:", Subscription::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Schedule Slots:", ScheduleSlot::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Clinic Services:", ClinicService::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Medical Center Services:", MedicalCenterService::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Clinic Gallery Images:", ClinicGalleryImage::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Medical Center Gallery Images:", MedicalCenterGalleryImage::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Loyalty Transactions:", LoyaltyTransaction::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Waiting Lists:", WaitingList::count());
        echo sprintf("║  %-30s %10s                        ║\n", "Invitations:", Invitation::count());
        echo sprintf("║  %-30s %10s                        ║\n", "FCM Tokens:", UserFcmToken::count());
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "🔐 Test Credentials:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Admin:      admin@clinichub.com / Admin@12345\n";
        echo "Doctor:     doctor1@test.com / Doctor@123\n";
        echo "Patient:    patient1@test.com / Patient@123\n";
        echo "Secretary:  sec1@test.com / Secretary@123\n";
        echo "Clinic:     clinic_shifa / password123\n";
        echo "Center:     center_damascus / Center@123\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
