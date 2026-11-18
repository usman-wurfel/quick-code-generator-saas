import { BaseLeadFormAnswer } from './base-answer'

export class StarsAnswer extends BaseLeadFormAnswer {
    slug() {
        return 'stars'
    }

    onDocumentClick(e) {
        if (!this.isChildOfBoundForm(e.target)) return

        if (e.target.closest('.star')) {
            this.onStarClick(e)
        }
    }

    async onStarClick(e) {
        const number = this.getStarNumber(e.target.closest('.star'))

        this.resetAllStars(e.target.closest('.stars-container'))

        this.enableStarsBeforeOrEqual(
            number,
            e.target.closest('.stars-container')
        )

        await this.flashAnimate(e.target.closest('.star'))

        this.requestScrollDown()
    }

    resetAllStars(container) {
        const elems = Array.from(container.querySelectorAll('.star'))

        elems.forEach((elem) => this.setStarEnabled(elem, false))
    }

    setStarEnabled(elem, enabled) {
        if (enabled) {
            elem.classList.add('enabled')
        } else {
            elem.classList.remove('enabled')
        }
    }

    enableStarsBeforeOrEqual(number, container) {
        for (let i = 0; i <= number; i++) {
            const elem = container.querySelector(`.star:nth-child(${i + 1})`)

            this.setStarEnabled(elem, true)
        }
    }

    getStarNumber(elem) {
        const i = Array.from(elem.parentNode.children).indexOf(elem)

        return i === -1 ? false : i
    }

    shouldDisableOKButton() {
        return !this.jsonGetValue(this.nextPage)
    }

    shouldUpdateOKButtonDisabledState() {
        return true
    }

    jsonGetValue(questionPage) {
        return questionPage.querySelectorAll('.star.enabled').length
    }
}

StarsAnswer.boot()
