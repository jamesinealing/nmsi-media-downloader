
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
  <meta name="description" content="">
  <meta name="author" content="James Morley">

  <title>Science Museum data and media downloader</title>

  <!-- Bootstrap core CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>

<body>



  <div class="container-fluid">
    <div class="row">
      <h1>Science Museum Media Downloader</h1>
      <p>Use this tool to create a download of Science Museum data &amp; media - a simple csv version of the metadata plus copies of all the media in a convenient zip file.</p>
      <ol>
        <li>Choose an arbitrary but memorable username for your downloads (e.g. your Twitter name)</li>
        <li>Describe your download so others can use it too</li>
        <li>Paste in the full url copied from a <a href="http://collection.sciencemuseum.org.uk/search/" target="_blank">search on the public site</a>. <em>Known issue: does not currently work when search or category text has commas</em></li>
        <li>Choose the maximum number of records to be retrieved</li>
        <li>Request the download &amp; wait!</li>
        <li>Questions/problems? Contact <a href="https://twitter.com/jamesinealing">@jamesinealing</a> | <a href="mailto:jamesinealing@gmail.com">jamesinealing@gmail.com</a></li>
      </ol>
    </div>
    <div class="row" id="newDownload">
      <h2>Create download</h2>
      <form class="form-horizontal">
        <div class="form-group col-sm-12">
          <label for="yourName" class="col-sm-2 control-label">Your Username*</label>
          <div class="col-sm-3">
            <input type="text" class="form-control" id="yourName" name="yourName" placeholder="e.g. jamesinealing">
          </div>
          <label for="queryName" class="col-sm-2 control-label">Dataset Name*</label>
          <div class="col-sm-3">
            <input type="text" class="form-control" id="queryName" name="queryName" placeholder="e.g. Pictures of rockets">
          </div>
        </div>

        <div class="form-group col-sm-12">
          <label for="queryUrl" class="col-sm-2 control-label">Collections search url*</label>
          <div class="col-sm-8">
            <input type="text" class="form-control" id="queryUrl" name="queryUrl" placeholder="e.g. http://collection.sciencemuseum.org.uk/search?filter%5Bhas_image%5D=true&filter%5Bimage_license%5D=true&filter%5Bmakers%5D= ...">
          </div>
        </div>
        <div class="form-group col-sm-12">
          <label for="recordCount" class="col-sm-2 control-label">Number of records to return</label>
          <div class="col-sm-3">
            <select class="form-control" name="recordCount" id="recordCount">
              <option value="100">100</option>
              <option value= "500">500</option>
              <option value="1000" selected>1,000</option>
              <option value="5000">5,000</option>
              <option value="5000">10,000 (max)</option>
            </select>
          </div>
          <label for="mediaLicense" class="col-sm-2 control-label">Select License</label>
          <div class="col-sm-3">
            <select class="form-control" name="mediaLicense" id="mediaLicense">
              <option value="filter%5Bimage_license%5D=true">Non-commercial use</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary col-sm-2">Request download</button>
        </div>

      </form>
    </div>
    <div class="row">
      <h2>Existing Downloads</h2>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Username</th>
              <th>Dataset</th>
              <th>Search url</th>
              <th>Records</th>
              <th>Download</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $requests=fopen('requests.csv','r');
            //print_r($requests);
            while (($request=fgetcsv($requests)) !== false) {
              //print_r($request);
              echo sprintf('<tr><td>%s</td><td>%s</td><td><a href="%s" target="_blank">View records</a></td><td>%s</td><td><a href="downloads/%s.zip">download zip</a></td></tr>',$request[1],$request[2],$request[3],$request[4],$request[0]);
            }
            ?>
          </tbody>
        </table>
      </div> <!-- // table responsive -->

    </div>
  </div><!-- /.container -->


  <!-- Bootstrap core JavaScript
  ================================================== -->
  <!-- Placed at the end of the document so the pages load faster -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  <script type='text/javascript'>
  /* attach a submit handler to the form */
  $("form").submit(function(e){
    e.preventDefault();

    $("#newDownload").after('<div class="row" id="response"><div class="alert alert-success alert-dismissable" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Your download has been initiated. Please leave this page open and an alert will display when it is completed and ready. Allow about 10 minutes per 1,000 records</div></div>').fadeIn(3000);


    var formData = $('form').serialize();
    $.get({
      type: "get",
      url: "fetch-data.php",
      data: formData,
      contentType: "application/x-www-form-urlencoded",
      success: function(responseData, textStatus, jqXHR) {
        //alert("download created");
        $("#response").html('<div class="alert alert-success alert-dismissable" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><b>Success </b><br>'+responseData+'</div>');
      },
      error: function(jqXHR, textStatus, errorThrown) {
        $("#response").html('<div class="alert alert-danger alert-dismissable" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><b>Error</b><br>One or more missing fields</div>');
      }
    });

    // disable form and empty data inputs
    //$("#newDownload :input").prop("disabled", true);
    $("#newDownload").each(function(){
      this.reset();
    });
  });
  </script>

</body>
</html>
