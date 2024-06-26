<?php
/*
Plugin Name: Financial Report
Plugin URI: www.example.com
Description: Plugin for financial total calculation
Version: 1.0.0
Author: Your Name
Author URI: www.example.com
Text Domain: financial-report
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Add menu item to the admin panel
function financial_report_menu() {
    add_menu_page(
        'Financial Report',
        'Financial Report',
        'manage_options',
        'financial-report',
        'display_financial_report_page'
    );
}
add_action('admin_menu', 'financial_report_menu');

// Enqueue styles and scripts
function financial_enqueue_scripts() {
    wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css', array(), '4.5.2');
    wp_enqueue_script('jquery');
    wp_enqueue_script('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), '4.5.2', true);
}
add_action('admin_enqueue_scripts', 'financial_enqueue_scripts');

// Display financial report page in the admin panel
function display_financial_report_page() {
    ?>
    <div class="wrap">
        <h1>Financial Report</h1>
        <?php
        // Calculate daily total sales for current date
        $daily_total_sales = calculate_daily_total_sales();

        // Display daily total sales box
        echo '<div class="alert alert-primary" role="alert">';
        echo '<h4 class="alert-heading">Daily Total Sales (' . date('Y-m-d') . ')</h4>';
        echo '<p>Total Cash: ' . $daily_total_sales['total_cash'] . '</p>';
        echo '<p>Total Credit: ' . $daily_total_sales['total_credit'] . '</p>';
        echo '</div>';
        ?>
        <form method="post" action="" onsubmit="return validateForm()">
            <div class="row">
                <div class="col-8">
                    <div class="row">
                        <div class="col-9">
                            <div class="form-group">
                                <label for="selected_date">Select Date:</label>
                                <input type="date" class="form-control" id="selected_date" name="selected_date">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="selected_date">Get Data:</label>
                            <button type="submit" class="btn btn-primary" name="submit">Fetch Report</button>
                        </div>
                    </div>
                </div>
                <div class="col-4"></div>
            </div>
        </form>
        <br>
        <div class="container">
            <?php
            if (isset($_POST['submit']) && isset($_POST['selected_date'])) {
                $selected_date = sanitize_text_field($_POST['selected_date']);
                if (empty($selected_date)) {
                    echo '<p style="color: red;">Please select a date before fetching data.</p>';
                } else {
                    $financial_report = generate_financial_report($selected_date);
                    if (!empty($financial_report)) {
                        // Calculate totals
                        $total_cash = $total_credit = 0;
                        foreach ($financial_report as $row) {
                            if ($row['Term'] === 'Cash') {
                                $total_cash += $row['Sale'];
                            } elseif ($row['Term'] === 'Credit') {
                                $total_credit += $row['Sale'];
                            }
                        }

                        // Calculate total of both totals
                        $total_sales = $total_cash + $total_credit;

                        // Display totals
                        echo '<div class="row">';
                        echo '<div class="col-md-4">';
                        echo '<div class="alert alert-info" role="alert">';
                        echo '<h4 class="alert-heading">Total Cash</h4>';
                        echo '<p>Total cash sales: ' . $total_cash . '</p>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="col-md-4">';
                        echo '<div class="alert alert-warning" role="alert">';
                        echo '<h4 class="alert-heading">Total Credit</h4>';
                        echo '<p>Total credit sales: ' . $total_credit . '</p>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="col-md-4">';
                        echo '<div class="alert alert-success" role="alert">';
                        echo '<h4 class="alert-heading">Total Sales</h4>';
                        echo '<p>Total sales: ' . $total_sales . '</p>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';

                        // Display financial report table
                        echo '<h2>Financial Report for ' . $selected_date . '</h2>';
                        echo '<table class="table">';
                        echo '<thead><tr><th>Term</th><th>Sale</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($financial_report as $row) {
                            echo '<tr>';
                            echo '<td>' . $row['Term'] . '</td>';
                            echo '<td>' . $row['Sale'] . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';

                        // Download button for CSV
                        echo '<div style="text-align: right;">';
                        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
                        echo '<input type="hidden" name="action" value="download_csv">';
                        echo '<input type="hidden" name="financial_data" value="' . base64_encode(serialize($financial_report)) . '">';
                        echo '<button type="submit" class="btn btn-success">Download CSV Report</button>';
                        echo '</form>';
                        echo '</div>';
                    } else {
                        echo '<p>No data available for the selected date.</p>';
                    }
                }
            }
            ?>
        </div>
    </div>
    <script>
        // JavaScript function to validate date selection
        function validateForm() {
            var selectedDate = document.getElementById('selected_date').value;
            if (selectedDate === '') {
                alert('Please select a date before fetching data.');
                return false;
            }
            return true;
        }
    </script>
    <?php
}

// Financial report query function
function generate_financial_report($selected_date) {
    global $wpdb;

    // Correct database credentials
    $servername = "localhost";
    $username = "gqrrefmy_colombostores";
    $password = "0duPYVS~fguM";
    $dbname = "gqrrefmy_colombostores";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to fetch financial report
    $sql = "SELECT t.Type AS 'Term', SUM(t.Sales) AS 'Sale' FROM (SELECT IF(dt.payment_type ='C','Cash', IF(dt.payment_type ='S','Credit',IF(dt.payment_type ='_','Return','Other'))) AS 'Type', SUM(IF(payment_type = '_',  dt.amount*-1, dt.amount)) AS 'Sales', order_id FROM wp_apbd_pos_cash_drawer_types dt WHERE CAST(entry_time AS DATE)='$selected_date' GROUP BY order_id) AS t GROUP BY t.Type";

    // Execute the query
    $result = $conn->query($sql);

    // Store the results in an array
    $financial_report = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $financial_report[] = $row;
        }
    }

    // Close the connection
    $conn->close();

    // Return the financial report
    return $financial_report;
}

// Action hook for handling CSV download
add_action('admin_post_download_csv', 'download_csv_callback');
function download_csv_callback() {
    if (isset($_POST['financial_data'])) {
        $financial_report = unserialize(base64_decode($_POST['financial_data']));

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="financial_report_' . $current_date . '.csv"');
        
        // Open file handler
        $file = fopen('php://output', 'w');

        // Write CSV headers
        fputcsv($file, array('Term', 'Sale'));

        // Write CSV data
        foreach ($financial_report as $row) {
            fputcsv($file, $row);
        }

        // Close file handler
        fclose($file);

        // Exit script
        exit();
    }
}

// Calculate daily total sales for the current date
function calculate_daily_total_sales() {
    global $wpdb;

    // Correct database credentials
    $servername = "localhost";
    $username = "gqrrefmy_colombostores";
    $password = "0duPYVS~fguM";
    $dbname = "gqrrefmy_colombostores";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Current date
    $current_date = date('Y-m-d');

    // Query to fetch daily total sales
    $sql = "SELECT IF(payment_type ='C','Cash','Credit') AS 'Type', SUM(IF(payment_type = '_',  amount*-1, amount)) AS 'Sales' FROM wp_apbd_pos_cash_drawer_types WHERE CAST(entry_time AS DATE)='$current_date' GROUP BY payment_type";

    // Execute the query
    $result = $conn->query($sql);

    // Initialize totals
    $total_sales = array(
        'total_cash' => 0,
        'total_credit' => 0
    );

    // Store the results in totals array
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['Type'] === 'Cash') {
                $total_sales['total_cash'] = $row['Sales'];
            } elseif ($row['Type'] === 'Credit') {
                $total_sales['total_credit'] = $row['Sales'];
            }
        }
    }

    // Close the connection
    $conn->close();

    // Return the total sales
    return $total_sales;
}
?>
