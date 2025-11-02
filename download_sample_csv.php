<?php
// Force browser to download the CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sample_student_enrollment.csv"');

// CSV Header Columns
$header = [
    'Register Number', 
    'First Name', 
    'Last Name', 
    'Email', 
    'Phone', 
    'Date of Birth (YYYY-MM-DD)', 
    'Gender (Male/Female/Other)', 
    'Address', 
    'Class (e.g., MCA I)', 
    'Parent Name', 
    'Parent Phone', 
    'Admission Date (YYYY-MM-DD)',
    'Profile Picture (Optional - image file name or URL)'
];

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
fputcsv($output, $header);

// Optional sample student data (example)
fputcsv($output, [
    'MCA001',
    'John',
    'Doe',
    'john.doe@example.com',
    '9876543210',
    '2000-01-15',
    'Male',
    '123 Example Street, Chennai',
    'MCA I',
    'Robert Doe',
    '9876543211',
    '2023-07-01',
    'john_doe.jpg' // Profile Picture
]);

fclose($output);
exit;
?>
