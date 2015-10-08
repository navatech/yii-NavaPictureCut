<style>
    #JtuyoshiCrop #Painel {
        font-size: 12px;
        padding-bottom: 10px;
        margin: 0
    }

    #JtuyoshiCrop #Principal {
        position: relative;
        margin: 0
    }

    #JtuyoshiCrop #SelecaoRecorte {
        position: absolute;
        background-color: #FFF;
        border: 2px #333 dotted;
        opacity: 0.5
    }

    #JtuyoshiCrop .row {
        padding: 0.5em 1em;
    }

    .ui-dialog .ui-dialog-title {
        font-weight: normal;
        padding: 0;
    }
</style>
<div id="Principal">
    <div id='SelecaoRecorte'></div>
</div>
<div id="Painel">
    <div class="row" style="text-align: center">
        <div style="display:none;">
            <select style="display: none;" id="SelectProporcao" title="Choose the Aspect Ratio of selection"
                    class="form-control">
            </select>
        </div>
        <div class="col-xs-12 col-md-12">
            <div class="btn-group">
                <button id="button_crop_recortar" class="btn btn-primary">Lưu lại</button>
                <button id="button_crop_original" class="btn btn-default" style="display: none;"></button>
                <button id="button_crop_cancel" class="btn btn-default"></button>
            </div>
        </div>

    </div>

</div>
