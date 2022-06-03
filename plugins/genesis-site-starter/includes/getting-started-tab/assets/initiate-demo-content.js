// Send a fetch call to spin up the demo products.

var gssDemoContentImportIsComplete = false;

fetch( gss_initiate_demo_content.spin_up_demo_content_endpoint_url, {
	method: "POST",
	mode: "same-origin",
	credentials: "same-origin",
} ).then(function() {
	// When fetch is complete, hide the importing notifier.
	document.getElementById("gss-importing").style.display = "none";
	// Show the success message.
	document.getElementById("gss-import-success").style.display = "block";

	gssDemoContentImportIsComplete = true;
} );

window.addEventListener("beforeunload", function (e) {
	if ( ! gssDemoContentImportIsComplete ) {
		(e || window.event).returnValue = true;
		return true;
	}
 });
