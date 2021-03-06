<!-- Modal -->
<div id="modal-add-grading" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form id="form-add-grading" action="#" method="post" class="row">
                    {{ csrf_field() }}
                    <input hidden :value="grade_data.id"   name="id">
                    <div class="col-md-12">
                        <input name="name" :value="grade_data.name"  placeholder="O Level grading" class="form-control" type="text" required>
                    </div>


                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button @click="addGrading" type="button"  class="btn btn-primary" >Submit</button>
            </div>
        </div>

    </div>
</div>