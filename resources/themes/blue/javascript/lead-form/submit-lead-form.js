import { Config } from '../config'
import { BaseLeadFormAnswer } from './answers/base-answer'
import { BaseLeadFormRenderer } from './base-lead-form-renderer'

class SubmitLeadForm extends BaseLeadFormRenderer {
    onDocumentClick(e) {
        if (!this.isChildOfBoundForm(e.target)) return

        if (e.target.closest('.ok-button.submit')) {
            this.onSubmitClick(e)
        }
    }

    isButtonDisabled(e) {
        const container = e.target.closest('.ok-button.submit')

        const btn = container.querySelector('.button')

        return btn.hasAttribute('disabled')
    }

    async onSubmitClick(e) {
        if (this.isButtonDisabled(e)) return

        const fields = this.getFields()

        const token = this.getCsrfToken()

        const leadFormId = this.getLeadFormId()

        const formData = this.buildFormData({ fields, token, leadFormId })

        this.onBeforePost(fields)

        try {
            await this.post(formData)

            this.onSuccess()
        } catch (err) {
            this.onPostError(err)
        }

        this.onAfterPost()
    }

    onBeforePost() {
        //
    }

    onPostError(err) {
        //
    }

    onAfterPost() {
        //
    }

    onSuccess() {
        document.dispatchEvent(
            new CustomEvent(SubmitLeadForm.EVENT_ON_SUCCESS, {
                detail: { form: this.form },
            })
        )
    }

    endpoint() {
        return Config.get('app.url') + '/api/lead-form-response'
    }

    getLeadFormId() {
        return this.form.dataset.id
    }

    async post(formData) {
        const response = await fetch(this.endpoint(), {
            method: 'POST',
            body: formData,
            headers: {
                Accept: 'application/json',
            },
        })

        if (!response.ok) {
            throw new Error('Error while submitting the form')
        }
    }

    buildFormData({ fields, token, leadFormId }) {
        const data = new FormData()

        data.append('fields', fields)

        data.append('_token', token)

        data.append('lead_form_id', leadFormId)

        return data
    }

    getCsrfToken() {
        return this.$('[name="csrf-token"]')?.getAttribute('content')
    }

    getFields() {
        let fields = this.f$$('.question-page').reduce(
            (result, questionPage) => {
                return this.form.renderers.reduce((result, renderer) => {
                    if (renderer instanceof BaseLeadFormAnswer) {
                        const json = renderer.toJson(questionPage)

                        if (json) {
                            result.push(json)
                        }
                    }

                    return result
                }, result)
            },
            []
        )

        fields = JSON.stringify(fields)

        return fields
    }
}

SubmitLeadForm.boot()
