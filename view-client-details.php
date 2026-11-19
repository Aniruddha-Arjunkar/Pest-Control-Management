<?php
session_start();
  if (!isset($_SESSION['email'])) {
  	$_SESSION['msg'] = "You must log in first";
  	header('location:login.php');
  }
  if (isset($_GET['logout'])) {
  	session_destroy();
  	unset($_SESSION['email']);
  	header('location:login.php');
  }
  ?>
<?php
require_once 'db_connect.php';
include 'header.php';
?>

<!--main content start-->
<section id="main-content">
    <section class="wrapper">
      <div class="table-agile-info" style="
    padding-left: 5px;padding-right: 5px">   

        <div class="panel panel-default">
<input type="text" id='txt_searchall' class="form-control search" placeholder=" Search">
         
        <div class="panel-heading">
          Client Details	 
        </div>
    <div>
    <table class="table" ui-jq="footable" ui-options='{
        "paging": {                           
        "enabled": true                          
        },                          
        "filtering": {                         
        "enabled": true                         
         },
        "sorting": {                          
        "enabled": true                          
         }}'>                                                   
        <thead>                        
         <tr>                           
                             
         <th>Service</th>
		 <th>Service Type</th>
		 <th>Name</th>
         <th>Phone</th>
		 <th>Email</th>
		 <th>Address</th>		 
		 <th>Total Amount</th>
		 <th>Paid Amount</th>
		 <th>Balance Amount</th>
		 <th>Due Date</th>
		 <th>Payment Date</th>
		
		 </tr>
		</thead>
								
                                <?php
                               
							 $sql = "SELECT c1.services,c1.service_type,c1.cust_name, c1.cust_phone, c1.cust_email,c1.cust_address,c1.total_amount,p1.total_amount,p1.paid_amount,p1.total_amount-p1.paid_amount AS due_amount,p1.payment_date,p1.due_date from tbl_client c1 inner join  tbl_payment p1 on c1.Id=p1.client_Id where c1.Id=p1.client_Id";
						
								
               $result = mysqli_query($conn, $sql);

                                if (mysqli_num_rows($result) > 0) {
                                    // output data of each row
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        ?>
                                        <tr>
                                          
                                            <td><?= $row["services"]; ?></td>
											<td><?= $row["service_type"]; ?></td>
                                            <td><?= $row["cust_name"]; ?></td>
                                            <td><?= $row["cust_phone"]; ?></td>
                                            <td><?= $row["cust_email"]; ?></td>
                                            <td><?= $row["cust_address"]; ?></td>
											<td><?= $row["total_amount"]; ?></td>
											<td><?= $row["paid_amount"]; ?></td>
											<td><?= $row["due_amount"]; ?></td>
											<td><?= $row["due_date"]; ?></td>
												<td><?= $row["payment_date"]; ?></td>
                                        </tr>
                                        <?php
                                    }
									} else {
                                  //  echo "0 results";
                                }
                                
                                ?>
                </table>
				
			</div>
		</div>
	</div>
</section>
            <!-- footer --
            <div class="footer">
                <div class="wthree-copyright">
                    <p>© 2017 Visitors. All rights reserved | Design by <a href="http://w3layouts.com">W3layouts</a></p>
                </div>
            </div>
            <!-- / footer -->
        </section>

        <!--main content end-->
    </section>
    <script src="js/bootstrap.js"></script>
    <script src="js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/jquery.nicescroll.js"></script>
    <!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot-chart/excanvas.min.js"></script><![endif]-->
    <script src="js/jquery.scrollTo.js"></script>
    <script src="js/search.js"></script>
</body>
</html>
