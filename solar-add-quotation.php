
<?php
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

/* =========================
   DATABASE CONNECTION
========================= */
require_once 'db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

/* =========================
   ESCAPE FUNCTION
========================= */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   GET SOLAR INQUIRY ID
========================= */
$inquiry_id = (int)($_GET['inquiry_id'] ?? 0);

if ($inquiry_id <= 0) {
    die("Invalid Inquiry ID");
}

/* =========================
   FETCH SOLAR INQUIRY
========================= */
$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM tbl_solar_inquiry
    WHERE id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $inquiry_id);
mysqli_stmt_execute($stmt);

$inq = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$inq) {
    die("Solar Inquiry Not Found");
}

/* =========================
   AUTO REF NO
========================= */
$query = mysqli_query($conn, "
    SELECT ref_no
    FROM tbl_solar_quotation
    ORDER BY id DESC
    LIMIT 1
");

if (!$query) {
    die("Quotation Query Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($query) > 0) {

    $row = mysqli_fetch_assoc($query);

    $lastRef = $row['ref_no'];

    $parts = explode('-', $lastRef);

    $lastNo = intval(end($parts));

    $newNo = $lastNo + 1;

} else {

    $newNo = 29;
}

$ref_no = "ME-QTY-" . $newNo;

/* =========================
   HEADER
========================= */
include('header.php');
?>

<div class="reg-w3">

    <div class="refollowup-card">

        <!-- ================= HEADER ================= -->
        <div class="refollowup-header">

            <h2 class="refollowup-title">
                Add Solar Quotation
            </h2>

        </div>

        <div class="card-body">

            <form action="solar-save-quotation.php"
                  method="POST"
                  class="refollowup-form">

                <input type="hidden"
                       name="inquiry_id"
                       value="<?= $inquiry_id; ?>">

                <!-- ================= QUOTATION INFO ================= -->
                <h4>Quotation Information</h4>
                <hr>

                <div class="form-row">

                    <div class="form-group">

                        <label>Ref No</label>

                        <input
                            type="text"
                            name="ref_no"
                            value="<?= e($ref_no); ?>"
                            class="form-control"
                            readonly>

                    </div>

                    <div class="form-group">

                        <label>Quotation Date <span class="text-danger">*</span></label>

                        <input
                            type="date"
                            name="quotation_date"
                            class="form-control"
                            value="<?= date('Y-m-d'); ?>"
                            required>

                    </div>

                </div>

                <!-- ================= CLIENT DETAILS ================= -->
                <h4>Client Details</h4>
                <hr>

                <div class="form-row">

                    <div class="form-group">

                        <label>Client Name</label>

                        <input
                            type="text"
                            class="form-control"
                            value="<?= e($inq['name'] ?? ''); ?>"
                            readonly>

                    </div>

                    <div class="form-group">

                        <label>Phone</label>

                        <input
                            type="text"
                            class="form-control"
                            value="<?= e($inq['phone'] ?? ''); ?>"
                            readonly>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label>Address</label>

                        <textarea
                            rows="2"
                            class="form-control"
                            readonly><?= e($inq['address'] ?? ''); ?></textarea>

                    </div>

                    <div class="form-group">

                        <label>Phase Type <span class="text-danger">*</span></label>

                        <select
                            name="property_type"
                            id="property_type"
                            class="form-control"
                            required
                            onchange="calculateSummary()">

                            <option value="">Select</option>

                            <option value="Residential - Single Phase">
                                Residential - Single Phase
                            </option>

                            <option value="Residential - Three Phase">
                                Residential - Three Phase
                            </option>

                            <option value="Commercial - Single Phase">
                                Commercial - Single Phase
                            </option>

                            <option value="Commercial - Three Phase">
                                Commercial - Three Phase
                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label>Quotation Subject <span class="text-danger">*</span></label>

                        <textarea
                            name="subject"
                            rows="2"
                            class="form-control"
                            required></textarea>

                    </div>

                </div>

                <!-- ================= SUMMARY OF PROPOSAL ================= -->
                <h4>Summary of Proposal</h4>
                <hr>

                <div class="form-row">

                    <div class="form-group">

                        <label>PV Plant Size (KWp) <span class="text-danger">*</span></label>

                        <input
                            type="number"
                            step="0.1"
                            name="plant_capacity_kw"
                            id="plant_capacity_kw"
                            class="form-control"
                            required
                            oninput="calculateSummary()">

                    </div>

                    <div class="form-group">

                        <label>System Type</label>

                        <input
                            type="text"
                            name="system_type"
                            value="Grid-Tie"
                            class="form-control"
                            readonly>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label>Power Generation / Month (kWh)</label>

                        <input
                            name="monthly_generation"
                            id="monthly_generation"
                            class="form-control"
                            readonly>

                    </div>

                    <div class="form-group">

                        <label>Power Generation / Year (kWh)</label>

                        <input
                            name="yearly_generation"
                            id="yearly_generation"
                            class="form-control"
                            readonly>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label>Min. Annual Saving (INR)</label>

                        <input
                            name="annual_saving"
                            id="annual_saving"
                            class="form-control"
                            readonly>

                    </div>

                    <div class="form-group">

                        <label>Investment Recovery (Years)</label>

                        <input
                            name="payback_years"
                            id="payback_years"
                            class="form-control"
                            readonly>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label>Total System Cost (INR) <span class="text-danger">*</span></label>

                        <input
                            type="number"
                            name="total_amount"
                            id="total_amount"
                            class="form-control"
                            required
                            oninput="calculateSummary()">

                    </div>

                </div>

                <!-- ================= COMPONENT DETAILS ================= -->
                <h4>Component Details</h4>
                <hr>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead class="thead-light">

                            <tr>
                                <th>Item</th>
                                <th>Specification</th>
                                <th>Make</th>
                                <th>Quantity / Standard</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td><strong>Solar Panels</strong></td>

                                <td>
                                    <textarea
                                        name="panel_spec"
                                        rows="3"
                                        class="form-control"></textarea>
                                </td>

                                <td>
                                    <input
                                        name="panel_make"
                                        class="form-control">
                                </td>

                                <td>
                                    <input
                                        name="panel_qty"
                                        class="form-control">
                                </td>

                            </tr>

                            <tr>

                                <td><strong>Inverter</strong></td>

                                <td>
                                    <textarea
                                        name="inverter_spec"
                                        rows="3"
                                        class="form-control"></textarea>
                                </td>

                                <td>
                                    <input
                                        name="inverter_make"
                                        class="form-control">
                                </td>

                                <td>
                                    <input
                                        name="inverter_qty"
                                        class="form-control">
                                </td>

                            </tr>

                            <tr>

                                <td><strong>Cables / Wires</strong></td>

                                <td>As per IEC Standards</td>

                                <td>
                                    <input
                                        name="cable_make"
                                        class="form-control">
                                </td>

                                <td class="text-muted text-center">
                                    As per IEC
                                </td>

                            </tr>

                            <tr>

                                <td><strong>Mounting Structure</strong></td>

                                <td>HDGI Structure</td>

                                <td>
                                    <input
                                        name="mounting_make"
                                        class="form-control">
                                </td>

                                <td class="text-muted text-center">
                                    Standards
                                </td>

                            </tr>

                            <tr>

                                <td><strong>DC Junction Box</strong></td>

                                <td>IP65 with SPD</td>

                                <td>
                                    <input
                                        name="dc_jb_make"
                                        class="form-control">
                                </td>

                                <td class="text-muted text-center">
                                    IEC 60364-5-53
                                </td>

                            </tr>

                            <tr>

                                <td><strong>AC Distribution Board</strong></td>

                                <td>MCB + SPD</td>

                                <td>
                                    <input
                                        name="acdb_make"
                                        class="form-control">
                                </td>

                                <td class="text-muted text-center">
                                    IEC 60364-5-53
                                </td>

                            </tr>

                            <tr>

                                <td><strong>Earthing</strong></td>

                                <td>Copper Bonded Earthing</td>

                                <td>
                                    <input
                                        name="earthing_make"
                                        class="form-control">
                                </td>

                                <td>
                                    <input
                                        name="earthing_qty"
                                        class="form-control">
                                </td>

                            </tr>

                            <tr>

                                <td><strong>Net Meter</strong></td>

                                <td>Bi-directional Energy Meter</td>

                                <td>
                                    <input
                                        name="net_meter_make"
                                        class="form-control">
                                </td>

                                <td>
                                    <input
                                        name="net_meter_qty"
                                        class="form-control">
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

                <!-- ================= COMMERCIAL DETAILS ================= -->
                <h4>Commercial Details</h4>
                <hr>

                <div class="form-row">

                    <div class="form-group">

                        <label>Supply & Installation</label>

                        <input
                            name="supply_installation_amount"
                            id="supply_amount"
                            class="form-control"
                            readonly>

                    </div>

                    <div class="form-group">

                        <label>GST @5%</label>

                        <input
                            name="gst_5_amount"
                            id="gst5_amount"
                            class="form-control"
                            readonly>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label>Commercial Total</label>

                        <input
                            name="commercial_total"
                            id="commercial_total"
                            class="form-control"
                            readonly>

                    </div>

                    <div class="form-group">

                        <label>Govt. Subsidy</label>

                        <input
                            name="govt_subsidy_amount"
                            id="govt_subsidy"
                            class="form-control"
                            readonly>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label>Actual Project Cost</label>

                        <input
                            name="actual_project_cost"
                            id="actual_cost"
                            class="form-control"
                            readonly>

                    </div>

                </div>

                <!-- ================= ACTION BUTTONS ================= -->
                <div class="form-row mt-3">

                    <div class="form-group">

                        <button type="submit" class="submit-btn">

                            <i class="bi bi-save"></i>
                            Save Quotation

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- ================= CALCULATION SCRIPT ================= -->
<script>

function calculateSummary() {

    let kw = parseFloat(
        document.getElementById('plant_capacity_kw').value
    ) || 0;

    let totalCost = parseFloat(
        document.getElementById('total_amount').value
    ) || 0;

    let monthlyGen = kw * 120;
    let yearlyGen = monthlyGen * 12;
    let annualSaving = yearlyGen * 13;

    let payback = annualSaving > 0
        ? totalCost / annualSaving
        : 0;

    document.getElementById('monthly_generation').value =
        monthlyGen.toFixed(0);

    document.getElementById('yearly_generation').value =
        yearlyGen.toFixed(0);

    document.getElementById('annual_saving').value =
        annualSaving.toFixed(0);

    document.getElementById('payback_years').value =
        payback.toFixed(1);

    autoCommercialFromTotal();
}

function autoCommercialFromTotal() {

    let kw = parseFloat(
        document.getElementById('plant_capacity_kw').value
    ) || 0;

    let totalCost = parseFloat(
        document.getElementById('total_amount').value
    ) || 0;

    let propertyType =
        document.getElementById('property_type').value;

    let subsidy = 0;

    // SUBSIDY ONLY FOR RESIDENTIAL
    if (propertyType.includes('Residential')) {

        if (kw > 0 && kw <= 2) {

            subsidy = 68000;

        } else if (kw > 2) {

            subsidy = 78000;

        }

    }

    let supply = totalCost / 1.05;

    let gst5 = supply * 0.05;

    let commercialTotal = supply + gst5;

    let actualCost = commercialTotal - subsidy;

    document.getElementById('govt_subsidy').value =
        subsidy.toFixed(2);

    document.getElementById('supply_amount').value =
        supply.toFixed(2);

    document.getElementById('gst5_amount').value =
        gst5.toFixed(2);

    document.getElementById('commercial_total').value =
        commercialTotal.toFixed(2);

    document.getElementById('actual_cost').value =
        actualCost.toFixed(2);
}

calculateSummary();

</script>

<!-- ================= FOOTER SCRIPTS ================= -->
<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>

</body>
</html>
