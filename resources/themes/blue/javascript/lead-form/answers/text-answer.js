import { BaseLeadFormAnswer } from './base-answer'

export class TextAnswer extends BaseLeadFormAnswer {
    get input() {
        return this.nextPage?.querySelector('input')
    }

    slug() {
        return 'text'
    }

    bindInputEventIfNeeded() {
        if (this.inputEventBound) return

        this.input.addEventListener('input', this.onInput)

        this.inputEventBound = true
    }

    isNextPageRequired() {
        this.bindInputEventIfNeeded()

        if (!super.isNextPageRequired()) return false

        return this.getValue().length === 0
    }

    hideOKButton() {
        return
    }

    getValue() {
        const value = this.input.value

        if (!value) {
            return ''
        }

        return value.trim()
    }

    shouldUpdateOKButtonDisabledState() {
        return true
    }

    shouldDisableOKButton() {
        return this.getValue().length === 0
    }

    onInput = (e) => {
        const target = e.target

        if (target !== this.input) return

        this.updateRequiredState()
    }

    jsonGetValue(questionPage) {
        return questionPage.querySelector('input').value
    }
}

TextAnswer.boot()
