<?php
$managePage = 'Service Invoice';
$editPage = base_url() . "Invoice/addInvoice/";
$deletePage = base_url() . "Invoice/delete/";
$mainPage = base_url() . "Invoice/";
$invoicePage = base_url() . "Invoice/addInvoice/";

?>
<!DOCTYPE html>
<html>

<head>
    <?php $this->load->view("Includes/head.php"); ?>
    <style>
        .dataTables_scrollBody {
            position: visible !important;
        }
    </style>
</head>

<body class="dashboard-page sb-l-o sb-l-m">
    <!-- Start: Main -->
    <div id="main">
        <!-- Start: Header -->
        <?php $this->load->view("Includes/header.php"); ?>
        <!-- Start: Content-Wrapper -->
        <section id="content_wrapper">
            <!-- Start: Topbar -->
            <header id="topbar">
                <div class="topbar-left">
                    <ol class="breadcrumb">
                        <li class="crumb-active">
                            <a href="<?= base_url('Dashboard') ?>">
                                Home
                                <span class="glyphicon glyphicon-home"></span>
                            </a>
                        </li>
                        <li class="crumb-trail">
                            <?= $managePage; ?>
                        </li>
                    </ol>
                </div>
            </header>

            <!-- End: Topbar -->
            <section id="content" class="animated fadeIn">
                <!-- Admin-panels -->
                <div class="admin-panels fade-onload">
                    <?php alertbox(); ?>
                    <div class="row">
                        <div class="col-lg-12 col-sm-12">
                            <div class="" id="spy2">
                                <div class="panel-heading">
                                    <div class="panel-title hidden-xs">
                                        <span class="fa fa-group"></span>
                                        <?= $managePage; ?>
                                    </div>
                                </div>

                                <table class="table table-striped table-hover" id="datatable2" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th style="text-align:center;">Bill</th>
                                            <th>Bill Date</th>
                                            <th style="width: 20%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($invoicelist)) : ?>
                                            <?php
                                                $i = 0;
                                                $i = $page;
                                                foreach ($invoicelist as $post) : $i++;

                                                    $date = new DateTime($post->billdate);
                                                    $billdate = $date->format('d/m/Y');

                                                    ?>
                                                <tr>
                                                    <td>
                                                        <?= $post->fname . " " . $post->lname; ?>
                                                        <br>
                                                        <a href="tel:<?= $post->mobile; ?>">
                                                            <?= $post->mobile; ?></a>
                                                    </td>

                                                    <td style="text-align:center;">
                                                        <?= $post->final_amt; ?><br>
                                                        <span class=" label label-success <?php if ($post->paid != "1") echo "label-warning"; ?>"><?= ($post->paid == "1") ? "Paid" : "Pending"; ?></span>
                                                    </td>
                                                    <td>
                                                        <?= $billdate;   ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                                echo form_open($deletePage . $post->id, [
                                                                    'id' => 'fd' . $post->id,
                                                                    'style' => 'float:left;margin-right:5px;'
                                                                ]);
                                                                ?>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-primary fs12 fw500 dropdown-toggle ph10" data-toggle="dropdown">
                                                                <span class="caret"></span>
                                                            </button>
                                                            <ul class="dropdown-menu pull-right" role="menu">

                                                                <?php
                                                                        if (check_role_assigned('invoice', 'edit')) {
                                                                            ?>
                                                                    <li><a href="<?= $editPage . $post->custid . "/" . $post->id; ?>"><span class="fa fa-edit" style="valign:center"></span> Edit Bill</a>
                                                                    </li>
                                                                <?php
                                                                        }
                                                                        if (check_role_assigned('invoice', 'add')) {
                                                                            ?>
                                                                    <li><a href="<?= $invoicePage . $post->custid; ?>"><span class="fa fa-edit" style="valign:center"></span> New Bill</a>
                                                                    </li>
                                                                <?php
                                                                        }
                                                                        if (check_role_assigned('invoice', 'delete')) {
                                                                            ?>
                                                                    <li>
                                                                        <a href="#" class="cancel" onclick="javascript:deleteBox('<?= $post->id ?>')"><span class="fa fa-close"></span> Delete Bill</a>
                                                                    </li>
                                                                <?php
                                                                        }

                                                                        ?>
                                                            </ul>
                                                        </div>
                                                        <?php echo form_close(); ?>

                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <input type="hidden" name="delid" id="delid" value="0">
            <div id='myModal' class='modal'>
                <div class="modal-dialog panel  panel-default panel-border top">
                    <div class="modal-content">
                        <div id='myModalContent'></div>
                    </div>
                </div>

            </div>
            <!-- Begin: Page Footer -->
            <?php $this->load->view("Includes/footer"); ?>
            <!-- End: Page Footer -->
        </section>
        <!-- End: Content-Wrapper -->
    </div>
    <!-- End: Main -->

    <!-- BEGIN: PAGE SCRIPTS -->

    <!-- jQuery -->
    <?php $this->load->view("Includes/footerscript"); ?>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            "use strict";
            // Init Theme Core      
            Core.init();

            $('#datatable2').dataTable({
                // dom: "Bfrtip",
                // dom: "rtip",
                order: [],
                "scrollX": true,
                dom: '<"top"fl>rt<"bottom"ip>'
                // select: true
            });
            // Init Admin Panels on widgets inside the ".admin-panels" container
            $('.admin-panels').adminpanel({
                grid: '.admin-grid',
                draggable: true,
                preserveGrid: true,
                mobile: false,
                onStart: function() {
                    // Do something before AdminPanels runs
                },
                onFinish: function() {
                    $('.admin-panels').addClass('animated fadeIn').removeClass('fade-onload');

                    // Init the rest of the plugins now that the panels
                    // have had a chance to be moved and organized.
                    // It's less taxing to organize empty panels
                    setTimeout(function() {

                    }, 300)

                },
                onSave: function() {
                    $(window).trigger('resize');
                }
            });
        });
    </script>
    <script type="text/javascript">
        function deleteBox(frmname) {
            $("#delid").val(frmname);
        }

        $('.cancel').click(function(e) {
            e.preventDefault();

            var delid = $("#delid").val();
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this Records!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "No, cancel plx!",
                    closeOnConfirm: false,
                    closeOnCancel: false
                },
                function(isConfirm) {
                    if (isConfirm) {

                        $("#fd" + delid).submit();

                    } else {
                        swal({
                            title: "Cancelled",
                            text: "Your Records are safe :)",
                            type: "error",
                            confirmButtonClass: "btn-danger"
                        });
                    }
                });

        });
    </script>
    <!-- END: PAGE SCRIPTS -->

</body>

</html>