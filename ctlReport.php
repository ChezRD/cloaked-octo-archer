<?php $INC_DIR = $_SERVER["DOCUMENT_ROOT"] . "/includes";?>

<?php require_once "$INC_DIR/header.php"; ?>
<?php require_once "$INC_DIR/functions.php"; ?>

    <!-- Clear MySQL data -->
<?php if (isset($_POST['table'])): ?>
    <?php clearMySqlTable($_POST['table']); ?>
    <?php //var_dump($_POST['table']); ?>
<?php endif ?>

    <!-- Custom page content -->
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1 top-margin-index-logo">
            <?php $xcrud = Xcrud::get_instance(); ?>
            <?php $xcrud->table('ctl_results'); ?>
            <?php $xcrud->unset_add(); ?>
            <?php echo $xcrud->render(); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1 top-margin-index-logo">
            <form role="form" action="ctlReport.php" method="post">
                <input type="hidden" name="table" value="ctl_results"/>
                <button type="submit" class="btn btn-danger">Clear All Rows</button>
            </form>
        </div>
    </div>
<?php require_once "$INC_DIR/footer.php";