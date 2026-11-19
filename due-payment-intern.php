<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

// Initialize variable to hold total due amount
$total_due_amount = 0;
?>

<!--main content start-->
<section id="main-content">
    <section class="wrapper">
        <div class="table-agile-info">
            <div class="panel panel-default">
                <input type="text" id='txt_searchall' class="form-control search" placeholder="Search by Name or Course">
                <div class="panel-heading">
                    Due Payments
                </div>
                <div>
                    <table class="table" id="payments-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Name</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Due Amount</th>
                                <th>Due Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch due payments from the database
                            $sql = "SELECT sa.student_id, sa.course_inquired, sa.name AS student_name, 
                                    p.total_amount, SUM(p.paid_amount) AS total_paid, 
                                    (p.total_amount - SUM(p.paid_amount)) AS due_amount, 
                                    MAX(p.due_date) AS due_date 
                                    FROM tbl_student_admissions sa
                                    LEFT JOIN tbl_payment p ON sa.student_id = p.client_id 
                                    GROUP BY sa.student_id 
                                    HAVING due_amount > 0 
                                    ORDER BY due_date ASC";

                            $result = mysqli_query($conn, $sql);

                            if (!$result) {
                                echo "Error: " . mysqli_error($conn);
                            } elseif (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    
                                    // Skip rows with null or empty student name
                                    if (empty($row['student_name'])) {
                                        continue;
                                    }

                                    // Add to total due amount
                                    $total_due_amount += $row["due_amount"];
                                    $dueDate = new DateTime($row["due_date"]);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row["course_inquired"]); ?></td>
                                        <td class="student-name"><?= htmlspecialchars($row["student_name"]); ?></td>
                                        <td><?= htmlspecialchars(number_format($row["total_amount"], 2)); ?></td>
                                        <td><?= htmlspecialchars(number_format($row["total_paid"], 2)); ?></td>
                                        <td><?= htmlspecialchars(number_format($row["due_amount"], 2)); ?></td>
                                        <td>
                                            <span class="due-date" data-due-date="<?= htmlspecialchars($dueDate->format('Y-m-d')); ?>">
                                                <?= htmlspecialchars($row["due_date"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="add-payment.php?Id=<?= urlencode($row['student_id']); ?>" class="btn btn-primary">Update</a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='7'>No results found</td></tr>"; // Adjust colspan to match the number of columns
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <section id="total-due-amount" style="margin-top: 20px;">
                    <h4>Total Due Amount (INR): <span id="total-due"><?= htmlspecialchars(number_format($total_due_amount, 2)); ?></span></h4>
                </section>
            </div>
        </div>
    </section>
</section>

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>

<script>
    function calculateTotalDue() {
        let totalDue = 0;

        document.querySelectorAll('#payments-table tbody tr').forEach(row => {
            if (row.style.display !== 'none') { // Only consider visible rows
                const dueAmount = parseFloat(row.cells[4].textContent.replace(/,/g, '')) || 0; // Cell index 4 for due amount
                totalDue += dueAmount;
            }
        });

        document.getElementById('total-due').textContent = totalDue.toFixed(2);
    }

    function searchPayments() {
        const query = document.getElementById('txt_searchall').value.toLowerCase();
        const rows = document.querySelectorAll('#payments-table tbody tr');

        rows.forEach(row => {
            const nameCell = row.querySelector('.student-name').textContent.toLowerCase();
            const courseCell = row.cells[0].textContent.toLowerCase(); // Adjust index for course

            if (nameCell.includes(query) || courseCell.includes(query)) {
                row.style.display = ''; // Show row
            } else {
                row.style.display = 'none'; // Hide row
            }
        });

        // Recalculate totals after search
        calculateTotalDue();
    }

    // Attach event listener to the search input
    document.getElementById('txt_searchall').addEventListener('input', searchPayments);

    window.onload = function() {
        calculateTotalDue(); // Calculate totals on load

        const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format

        document.querySelectorAll('.due-date').forEach(span => {
            const dueDate = span.getAttribute('data-due-date');
            if (dueDate <= today) {
                span.classList.add('highlight-today'); // Highlight if due date is today or earlier
            }
        });
    };
</script>

<style>
.highlight-today {
    background-color: pink; /* Pink background for due dates */
    padding: 2px; /* Optional padding */
    border-radius: 3px; /* Optional rounded corners */
}
</style>

</body>
</html>
