// Below Function Executes On Form Submit
function Validation() {
// Storing Field Values In Variables
{
	var cust_phone="/^\d{10}$/";
	if(document.getElementbyId("phoneno.").value=="")
		document.getElementbyId("phoneno.").innerHTML="Enter 10 Digit No. Only";
  return false;
}
else{
  return true;
  }
}	
