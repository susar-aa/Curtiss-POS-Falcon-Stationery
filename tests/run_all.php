<?php
/**
 * Curtiss POS - Master Production Test Runner (Stage 20)
 * Executes ALL available Stage 1–20 test suites sequentially in CLI mode.
 * Collects passed, failed, skipped, and total assertions per stage.
 * Any single stage failure marks the entire run as FAILED.
 */

$stages = [
    1  => 'verify_stage1.php',
    2  => 'verify_stage2.php',
    3  => 'verify_stage3.php',
    4  => 'verify_stage4.php',
    5  => 'verify_stage5.php',
    6  => 'verify_stage6.php',
    7  => 'verify_stage7.php',
    8  => 'verify_stage8.php',
    9  => 'verify_stage9.php',
    10 => 'verify_stage10.php',
    11 => 'verify_stage11.php',
    12 => 'verify_stage12.php',
    13 => 'verify_stage13.php',
    14 => 'verify_stage14.php',
    15 => 'verify_stage15.php',
    16 => 'verify_stage16.php',
    17 => 'verify_stage17.php',
    18 => 'verify_stage18.php',
    19 => 'verify_stage19.php',
    20 => 'verify_stage20.php',
    21 => 'verify_section1_enhancements.php',
    22 => 'verify_section2_enhancements.php',
    23 => 'verify_section3_enhancements.php',
    24 => 'verify_section4_enhancements.php',
    25 => 'verify_section5_enhancements.php',
    26 => 'verify_section6_enhancements.php',
    27 => 'verify_section7_enhancements.php',
    28 => 'verify_section8_enhancements.php',
    29 => 'verify_section9_enhancements.php',
    30 => 'verify_section10_enhancements.php',
    31 => 'verify_section11_enhancements.php',
    32 => 'verify_section12_enhancements.php',
    33 => 'verify_section13_enhancements.php',
    34 => 'verify_section14_enhancements.php',
    35 => 'verify_section15_enhancements.php',
    36 => 'verify_section16_enhancements.php',
    37 => 'verify_section17_enhancements.php',
    38 => 'verify_section18_final_audit.php',
];


require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BusinessDay.php';

echo "=========================================================\n";
echo "    CURTISS POS - MASTER REGRESSION TEST RUNNER          \n";
echo "=========================================================\n\n";

$phpPath = PHP_BINARY ? PHP_BINARY : 'C:\\xampp\\php\\php.exe';
$testsDir = __DIR__;

$resultsSummary = [];
$totalPassedOverall = 0;
$totalFailedOverall = 0;
$totalTestsOverall  = 0;
$anyFailed = false;

foreach ($stages as $stageNum => $fileName) {
    // Ensure active business day exists for tests
    try {
        $activeDay = BusinessDay::getActiveDay(1);
        if (!$activeDay) {
            BusinessDay::startDay(1, 20000.00, 1, "Regression Run Stage {$stageNum}");
        }
    } catch (Throwable $e) {}

    $filePath = $testsDir . DIRECTORY_SEPARATOR . $fileName;
    echo "Executing Stage {$stageNum} Suite [{$fileName}]... ";

    if (!file_exists($filePath)) {
        echo "SKIPPED (File not found)\n";
        $resultsSummary[] = [
            'stage'   => $stageNum,
            'suite'   => $fileName,
            'status'  => 'SKIPPED',
            'passed'  => 0,
            'failed'  => 0,
            'total'   => 0
        ];
        continue;
    }

    $cmd = "\"{$phpPath}\" \"{$filePath}\"";
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);

    $fullText = implode("\n", $output);

    // Parse assertions passed/failed count
    $passedCount = 0;
    $failedCount = 0;

    foreach ($output as $line) {
        if (strpos($line, '[PASS]') !== false) {
            $passedCount++;
        } elseif (strpos($line, '[FAIL]') !== false) {
            $failedCount++;
        }
    }

    $totalStageTests = $passedCount + $failedCount;

    if ($returnCode === 0 && $failedCount === 0) {
        echo "PASSED ({$passedCount}/{$totalStageTests} tests)\n";
        $status = 'PASSED';
    } else {
        echo "FAILED ({$failedCount} failed, {$passedCount} passed)\n";
        foreach ($output as $line) {
            if (strpos($line, '[FAIL]') !== false) {
                echo "  --> {$line}\n";
            }
        }
        $status = 'FAILED';
        $anyFailed = true;
    }

    $resultsSummary[] = [
        'stage'   => $stageNum,
        'suite'   => $fileName,
        'status'  => $status,
        'passed'  => $passedCount,
        'failed'  => $failedCount,
        'total'   => $totalStageTests
    ];

    $totalPassedOverall += $passedCount;
    $totalFailedOverall += $failedCount;
    $totalTestsOverall  += $totalStageTests;
}

echo "\n=========================================================\n";
echo "                 MASTER TEST SUMMARY                     \n";
echo "=========================================================\n";
printf("%-8s | %-20s | %-10s | %-8s | %-8s | %-8s\n", "Stage", "Suite", "Status", "Passed", "Failed", "Total");
echo "---------------------------------------------------------\n";

foreach ($resultsSummary as $res) {
    printf("%-8s | %-20s | %-10s | %-8d | %-8d | %-8d\n",
        "Stage " . $res['stage'],
        $res['suite'],
        $res['status'],
        $res['passed'],
        $res['failed'],
        $res['total']
    );
}

echo "---------------------------------------------------------\n";
printf("%-31s | %-10s | %-8d | %-8d | %-8d\n",
    "TOTALS ACROSS ALL STAGES",
    $anyFailed ? "FAILED" : "PASSED",
    $totalPassedOverall,
    $totalFailedOverall,
    $totalTestsOverall
);
echo "=========================================================\n";

if ($anyFailed) {
    echo "OVERALL SYSTEM VALIDATION RESULT: FAILED\n";
    exit(1);
} else {
    echo "OVERALL SYSTEM VALIDATION RESULT: PASSED 100%\n";
    exit(0);
}
