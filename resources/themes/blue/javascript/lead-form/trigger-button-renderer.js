import { BodyScrollBlocker } from '../body-scroll-blocker'
import { BaseLeadFormRenderer } from './base-lead-form-renderer'

class TriggerButtonRenderer extends BaseLeadFormRenderer {
    static boot() {
        const btns = Array.from(document.querySelectorAll('.lead-form-trigger'))

        btns.forEach((btn) => {
            const form = document.querySelector(btn.dataset.target)

            if (!form) return

            const renderer = new this(form)

            btn.renderer = renderer

            renderer.btn = btn
        })
    }

    constructor(formElement) {
        super(formElement)

        this.btn = null
    }

    shouldRun() {
        return true
    }

    onDocumentClick(e) {
        const btn = e.target.closest('.lead-form-trigger')

        if (!btn) return

        if (btn.dataset.target != this.btn.dataset.target) {
            return
        }

        this.onTriggerButtonClick(e)
    }

    async onTriggerButtonClick(e) {
        const button = e.target

        this.removeClosedClass()

        await this.resetLeadFormStyleAttribte()

        this.setLeadFormRectAs(button)

        await new Promise((r) => setTimeout(r))

        this.addOpeningClassToLeadForm()

        await this.resetLeadFormStyleAttribte()

        await this.transitionPromise(this.form)

        this.addOpenedClassToLeadForm()

        this.removeOpeningClassFromLeadForm()

        this.dispatchAfterOpen()

        BodyScrollBlocker.block()
    }

    removeClosedClass() {
        this.form.classList.remove('closed')
    }

    removeOpeningClassFromLeadForm() {
        this.form.classList.remove('opening')
    }

    addOpeningClassToLeadForm() {
        this.form.classList.add('opening')
    }

    addOpenedClassToLeadForm() {
        this.form.classList.add('opened')
    }

    async resetLeadFormStyleAttribte() {
        await new Promise((resolve) => setTimeout(resolve, 10))

        this.form.style = ''
    }

    setLeadFormRectAs(elem) {
        const px = (v) => `${v}px`

        const { height, width, top, left, bottom, right } =
            elem.getBoundingClientRect()

        this.form.style.top = px(top)
        this.form.style.left = px(left)
        this.form.style.width = px(width)
        this.form.style.height = px(height)
        this.form.style.bottom = px(bottom)
        this.form.style.right = px(right)
    }

    dispatchAfterOpen() {
        document.dispatchEvent(
            new CustomEvent(TriggerButtonRenderer.EVENT_AFTER_FORM_OPEN)
        )
    }
}

TriggerButtonRenderer.boot()
