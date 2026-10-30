<?php
$loginContent = file_get_contents(__DIR__ . '/../login.php');

$tests = [
    'City input exists with id custRegCity' => strpos($loginContent, 'id="custRegCity"') !== false,
    'City input has name="sehir"' => strpos($loginContent, 'name="sehir"') !== false,
    'City input is type="text" allowing typing' => strpos($loginContent, '<input type="text" id="custRegCity"') !== false,
    'City input has combobox role' => strpos($loginContent, 'role="combobox"') !== false,
    'Native datalist is removed to prevent browser duplicate popup' => strpos($loginContent, '<datalist') === false,
    'Custom dropdown container exists' => strpos($loginContent, 'id="custCityDropdown"') !== false,
    'Combobox JS logic exists' => strpos($loginContent, 'initAllCityComboboxes') !== false,
    'Old static select for customer is completely replaced' => strpos($loginContent, '<select id="custRegCity"') === false,
    'Barber city input exists with id barberCity' => strpos($loginContent, 'id="barberCity"') !== false,
    'Barber city input is type="text" allowing typing' => strpos($loginContent, '<input type="text" id="barberCity"') !== false,
    'Barber city custom dropdown container exists' => strpos($loginContent, 'id="barberCityDropdown"') !== false,
    'Old static select for barber is completely replaced' => strpos($loginContent, '<select id="barberCity"') === false,
    'Barber district sync function exists' => strpos($loginContent, 'syncBarberDistricts') !== false,
    'Customer city label has custCityLabel id without for attribute' => strpos($loginContent, 'id="custCityLabel"') !== false && strpos($loginContent, 'for="custRegCity"') === false,
    'Barber city label has barberCityLabel id without for attribute' => strpos($loginContent, 'id="barberCityLabel"') !== false && strpos($loginContent, 'for="barberCity"') === false,
    'Label click toggle is attached to both labels' => strpos($loginContent, 'label.addEventListener(\'click\', toggleDropdown)') !== false,
];

$allPassed = true;
foreach ($tests as $name => $passed) {
    if ($passed) {
        echo "[PASS] $name\n";
    } else {
        echo "[FAIL] $name\n";
        $allPassed = false;
    }
}

if (!$allPassed) {
    exit(1);
}
echo "All customer city tests passed successfully.\n";
