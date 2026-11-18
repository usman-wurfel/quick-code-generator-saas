import { BaseRenderer } from './base-renderer'

class SplashScreenRenderer extends BaseRenderer {
    constructor() {
        super()
    }

    shouldRun() {
        return !!this.$('.splash-screen')
    }

    onAllImagesLoaded() {
        setTimeout(() => {
            this.$('.splash-screen').remove()
        }, 3000)
    }
}

new SplashScreenRenderer()
