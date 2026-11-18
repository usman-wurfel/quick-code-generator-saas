<div class="block lead-form-block" id="{{ $model->getId() }}">
    @include('qrcode.components.lead-form.trigger', ['id' => $model->field('lead_form_id')])
    @include('qrcode.components.lead-form.lead-form', ['id' => $model->field('lead_form_id')])
</div>