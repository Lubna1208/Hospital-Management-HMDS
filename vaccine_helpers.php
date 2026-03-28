<?php

function vaccine_allowed_genders(): array
{
    return ["Both", "Male", "Female", "Other"];
}

function vaccine_allowed_age_units(): array
{
    return ["months", "years"];
}

function vaccine_allowed_max_age_units(): array
{
    return ["months", "years", "no_limit"];
}

function vaccine_no_limit_age_months(): int
{
    return 1200;
}

function vaccine_normalize_gender(?string $gender): string
{
    $gender = trim((string)$gender);
    $gender = strtolower($gender);

    if ($gender === "male") {
        return "Male";
    }

    if ($gender === "female") {
        return "Female";
    }

    if ($gender === "other") {
        return "Other";
    }

    if ($gender === "all" || $gender === "both") {
        return "Both";
    }

    return "";
}

function vaccine_calculate_age_months($dateOfBirth): ?int
{
    if ($dateOfBirth instanceof DateTimeInterface) {
        $today = new DateTimeImmutable("today");
        $diff = $dateOfBirth->diff($today);
        return ((int)$diff->y * 12) + (int)$diff->m;
    }

    if (is_string($dateOfBirth) && $dateOfBirth !== "") {
        try {
            $dob = new DateTimeImmutable($dateOfBirth);
            $today = new DateTimeImmutable("today");
            $diff = $dob->diff($today);
            return ((int)$diff->y * 12) + (int)$diff->m;
        } catch (Exception $e) {
            return null;
        }
    }

    return null;
}

function vaccine_age_to_months($value, string $unit): ?int
{
    $unit = strtolower(trim($unit));

    if ($unit === "no_limit") {
        return vaccine_no_limit_age_months();
    }

    if ($value === null || $value === "" || filter_var($value, FILTER_VALIDATE_INT) === false) {
        return null;
    }

    $value = (int)$value;
    if ($value < 0) {
        return null;
    }

    if ($unit === "years") {
        return $value * 12;
    }

    if ($unit === "months") {
        return $value;
    }

    return null;
}

function vaccine_gender_matches(string $patientGender, string $allowedGender): bool
{
    $patientGender = vaccine_normalize_gender($patientGender);
    $allowedGender = vaccine_normalize_gender($allowedGender);

    if ($allowedGender === "" || $allowedGender === "Both") {
        return true;
    }

    if ($patientGender === "") {
        return false;
    }

    return $patientGender === $allowedGender;
}

function vaccine_check_eligibility(array $vaccine, ?int $patientAgeMonths, ?string $patientGender): array
{
    if ($patientAgeMonths === null || $patientGender === null || trim($patientGender) === "") {
        return [
            "eligible" => false,
            "status" => "Profile Incomplete",
            "reason" => "Add your date of birth and gender in Patient Info to check eligibility.",
        ];
    }

    $minAge = isset($vaccine["min_age"]) ? (int)$vaccine["min_age"] : 0;
    $maxAge = isset($vaccine["max_age"]) ? (int)$vaccine["max_age"] : 0;
    $allowedGender = (string)($vaccine["gender_applicable"] ?? "Both");

    if ($patientAgeMonths < $minAge || $patientAgeMonths > $maxAge) {
        return [
            "eligible" => false,
            "status" => "Not Eligible",
            "reason" => "Patient age is outside the allowed range for this vaccine.",
        ];
    }

    if (!vaccine_gender_matches((string)$patientGender, $allowedGender)) {
        return [
            "eligible" => false,
            "status" => "Not Eligible",
            "reason" => "Patient gender does not match this vaccine's eligibility rule.",
        ];
    }

    return [
        "eligible" => true,
        "status" => "Eligible",
        "reason" => "Patient meets the age and gender requirements.",
    ];
}

function vaccine_age_label(array $vaccine): string
{
    $minAge = isset($vaccine["min_age"]) ? (int)$vaccine["min_age"] : 0;
    $maxAge = isset($vaccine["max_age"]) ? (int)$vaccine["max_age"] : 0;

    return vaccine_format_month_range($minAge, $maxAge);
}

function vaccine_format_age_months(?int $ageMonths): string
{
    if ($ageMonths === null) {
        return "Unknown";
    }

    if ($ageMonths < 24) {
        return $ageMonths . " month" . ($ageMonths === 1 ? "" : "s");
    }

    $years = intdiv($ageMonths, 12);
    $remainingMonths = $ageMonths % 12;

    if ($remainingMonths === 0) {
        return $years . " year" . ($years === 1 ? "" : "s");
    }

    return $years . " year" . ($years === 1 ? "" : "s") . " " . $remainingMonths . " month" . ($remainingMonths === 1 ? "" : "s");
}

function vaccine_format_month_range(int $minAgeMonths, int $maxAgeMonths): string
{
    if ($maxAgeMonths >= vaccine_no_limit_age_months()) {
        return vaccine_format_age_months($minAgeMonths) . " and above";
    }

    return vaccine_format_age_months($minAgeMonths) . " - " . vaccine_format_age_months($maxAgeMonths);
}

function vaccine_format_max_age(int $maxAgeMonths): string
{
    if ($maxAgeMonths >= vaccine_no_limit_age_months()) {
        return "No limit";
    }

    return vaccine_format_age_months($maxAgeMonths);
}

function vaccine_split_age_for_form(?int $ageMonths): array
{
    $ageMonths = (int)($ageMonths ?? 0);

    if ($ageMonths >= 24 && $ageMonths % 12 === 0) {
        return [
            "value" => (int)($ageMonths / 12),
            "unit" => "years",
        ];
    }

    return [
        "value" => $ageMonths,
        "unit" => "months",
    ];
}
