<?php
// Test the updated getProjectBenefits function
require_once 'config.php';
require_once 'sroi-expost/includes/functions.php';

// Test project ID 2
$project_id = 2;
echo "<h2>Testing getProjectBenefits function for project_id = $project_id</h2>";

$benefits_data = getProjectBenefits($conn, $project_id);

echo "<h3>Benefits found:</h3>";
echo "<pre>";
print_r($benefits_data);
echo "</pre>";

echo "<h3>Summary:</h3>";
echo "Total benefits: " . count($benefits_data['benefits']) . "<br>";

foreach ($benefits_data['benefits'] as $key => $benefit) {
    echo "Benefit $key: " . $benefit['detail'] . " (Beneficiary: " . $benefit['beneficiary'] . ", Source: " . $benefit['source_type'] . ")<br>";
}

mysqli_close($conn);
?>