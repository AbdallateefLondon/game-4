<link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/behaviour_addon.css">
<div class="content-wrapper">
       <section class="content">
        <?php $this->load->view('behaviour/report/_behaviour_report'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box removeboxmius">
                    <div class="box-header ptbnull"></div>
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('class_wise_rank_report'); ?></h3>
                    </div>
                    <div class="box-body">
                         <div class="table-responsive mailbox-messages overflow-visible">
                            <div class="download_label"><?php echo $this->lang->line('class_wise_rank_report'); ?></div>
                            <table class="table table-striped table-bordered table-hover example">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('rank'); ?></th>
                                        <th><?php echo $this->lang->line('class'); ?></th>
                                        <th><?php echo $this->lang->line('student'); ?> </th>
                                        <th><?php echo $this->lang->line('total_points'); ?></th>
                                        <th class="text-right noExport"><?php echo $this->lang->line('action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($classwiserank)) { ?>
                                    <?php } else {
                                        $totalpoints =''; $rank =0;
                                        foreach ($classwiserank as $rank_key => $classwiserank_value) {
                                           
                                           if ($rank_key != 0) {                                
                                                $totalpoints =  $classwiserank[$rank_key - 1]['totalpoints'] ;
                                            }

                                            if($totalpoints == $classwiserank_value['totalpoints']){
                                                $rank = $rank;
                                            }else{
                                                $rank = $rank + 1;
                                            }

                                            $pointclass = '';
                                            if($classwiserank_value['totalpoints'] < 0){
                                                $pointclass = 'danger';
                                            }
                                        ?>
                                            <tr class="<?php echo $pointclass; ?>">
                                                <td class="mailbox-name"> <?php echo $rank; ?></td>
                                                <td class="mailbox-name"> <?php echo $classwiserank_value['class']; ?></td>
                                                <td class="mailbox-name"> <?php echo $classwiserank_value['total_student']; ?></td>
                                                <td class="mailbox-name"> <?php echo $classwiserank_value['totalpoints']; ?></td>
                                                <td class="mailbox-date pull-right no-print">
                                                    <a href="#" data-placement="left" data-class-id="<?php echo $classwiserank_value['class_id']; ?>" data-toggle="modal" data-backdrop="static" class="btn btn-default btn-xs assignstudent" title="" data-original-title="<?php echo $this->lang->line('show'); ?>"><i class="fa fa-reorder"></i></a>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table><!-- /.table -->
                        </div>
                    </div>   
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="assignstudentmodel" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-dialogfullwidth" role="document">
        <div class="modal-content modal-media-content mt35">
            <div class="modal-header modal-media-header d-flex justify-content-between">
                <div>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="box-title"><?php echo $this->lang->line('assigned_incident'); ?></h4>
                </div>
                <div class="">    
                    <a class="btn btn-default btn-xs pull-right mr-1" id="print" title="<?php echo $this->lang->line('print'); ?>" onclick="printDiv()" ><i class="fa fa-print"></i></a>
                    <a class="btn btn-default btn-xs pull-right" id="btnExport" title="<?php echo $this->lang->line('excel'); ?>" onclick="fnExcelReport()"> <i class="fa fa-file-excel-o"></i> </a>
                </div>    
            </div>
            <div class="modal-body pt0 pb0 pl-1 pr0 relative clearboth">
                <div class="scroll-area-inside" id="headerTable">
                    <div class="mailbox-messages table-responsive" id="assignincident">
                        <div id="assignstudentdata"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function ($) {
  "use strict";
    $(document).on("click",".assignstudent",function() {
        $('#assignstudentmodel').modal({backdrop: "static"});
        var class_id = $(this).attr('data-class-id');
        $.ajax({
            url: '<?php echo base_url(); ?>behaviour/report/classwisepoint',
            method: 'POST',
            data:{class_id:class_id},
            dataType:'JSON',
            beforeSend: function () {
              $('#assignstudentdata').html('<center><?php echo $this->lang->line('loading'); ?>  <i class="fa fa-spinner fa-spin"></i></center>');
            },
            success:function(response){
              $('#assignstudentdata').html(response.page);
            }
        })
    });

    document.getElementById("print").style.display = "block";
    document.getElementById("btnExport").style.display = "block";

})(jQuery);
</script>
<script>
    function printDiv() {

        $("#visible").removeClass("hide");
        $(".download_label").addClass("hide");

        document.getElementById("print").style.display = "none";
        document.getElementById("btnExport").style.display = "none";
        var divElements = document.getElementById('assignincident').innerHTML;
        var oldPage = document.body.innerHTML;
        document.body.innerHTML =
                "<html><head><title></title></head><body>" +
                divElements + "</body>";
        window.print();
        document.body.innerHTML = oldPage;

        location.reload(true);
    }

 
</script>