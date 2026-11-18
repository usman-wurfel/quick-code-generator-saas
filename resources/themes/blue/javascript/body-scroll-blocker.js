import { BaseRenderer } from './base-renderer'

export class BodyScrollBlocker extends BaseRenderer {
    static #scrollTop = 0

    static block() {
        this.#scrollTop = window.scrollY

        document.body.classList.add('block-scroll')
    }

    static async unblock() {
        document.body.classList.remove('block-scroll')

        await new Promise((r) => setTimeout(r))

        window.scrollTo(0, this.#scrollTop)
    }
}

BodyScrollBlocker.boot()
