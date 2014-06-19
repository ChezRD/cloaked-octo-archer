
<?php $INC_DIR = $_SERVER["DOCUMENT_ROOT"] . "/includes";?>
<?php require_once "$INC_DIR/header.php"; ?>
<?php require_once "$INC_DIR/functions.php"; ?>

<!-- Custom page content -->
<div class="row top-margin-row" >
    <div class="row-fluid">
        <div class="col-md-6 col-md-offset-3 margin-bottom-form">
            <form id="<?php echo !isset($_REQUEST['deviceName']) == "" ? 'form-hide' : ''; ?>" class="bs-example form-horizontal" method="post" action="<?php echo "ctlSingle.php" ?>" >
                <div class="panel-group">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <b>Delete CTL File</b>
                            </h4>
                        </div>
                        <div id="collapseOne" class="panel-collapse collapse in">
                            <div class="panel-body">
                                <div class="well">
                                    <fieldset>
                                        <legend class="text-center">Delelte CTL from a single phone</legend>
                                        <div class="form-group">
                                            <label class="col-lg-4 control-label text-warning margin-left-editD text-center">Phone MAC Address:</label>
                                            <div class="col-lg-6">
                                                <input id="deviceName" type='text' name='deviceName'>
                                                <button type="submit" class="btn btn-default" id="submit">Submit</button>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <?php if ($_POST): ?>
                <?php if (!$_POST['deviceName'] == ""): ?>
                    <table id='upi' class="table table-striped table-hover">
                        <thead>
                            <th>Device Name</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Code</th>
                        </thead>
                            <tr class="requestRowCtl">
                                <td class="device"><?php echo "$_POST[deviceName]"; ?></td>
                                <td class="status"></td>
                                <td class="message"></td>
                                <td class="code"></td>
                            </tr>
                    </table>
                <?php endif ?>
            <?php endif ?>
        </div>
    </div>
</div>

<?php require_once "$INC_DIR/footer.php";
