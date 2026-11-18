import { BaseRenderer } from './base-renderer'

export class StackedLayout extends BaseRenderer {
    slug() {
        throw new Error('slug is not specified')
    }

    shouldRender() {
        return !!this.$('.qrcode-type-' + this.slug())
    }

    onDocumentClick = (e) => {
        const element = e.target

        if (element.matches('.menu-category')) {
            this.onMenuCategoryClick(e)
        }

        if (element.matches('.close-button')) {
            this.onMenuCategoryClose(e)
        }

        if (element.matches('.sub-category')) {
            this.onSubCategoryClick(e)
        }
    }

    onSubCategoryClick(e) {
        const subCategoryId = e.target.getAttribute('slug')

        const page = e.target.closest('.category-page')

        const allSubcategories = Array.from(
            page.querySelectorAll('.sub-category')
        )

        const allItems = Array.from(page.querySelectorAll('.menu-item'))

        const subCategoryItems = Array.from(
            page.querySelectorAll(
                `.menu-item[category-slug="${subCategoryId}"]`
            )
        )

        allSubcategories.forEach((subCategory) =>
            subCategory.classList.remove('active')
        )

        e.target.classList.add('active')

        if (e.target.matches('.show-all')) {
            page.classList.remove('sub-category-active')

            allItems.forEach((i) => i.classList.remove('current-sub-category'))

            return
        }

        page.classList.add('sub-category-active')

        allItems.forEach((item, i) => {
            item.classList.remove('current-sub-category')
            item.style.transitionDelay = i * 0.05 + 's'
        })

        setTimeout(() => {
            subCategoryItems.forEach((item) => {
                item.classList.add('current-sub-category')
            })
        }, 50)
    }

    onCategoryPageOpen = async (e) => {
        const { categoryPage, promise } = e.detail

        await promise

        const allItems = Array.from(categoryPage.querySelectorAll('.menu-item'))

        allItems.forEach((item) => {
            const initialHeight = item.getAttribute('initial-height')

            if (initialHeight) {
                item.style.maxHeight = initialHeight
            } else {
                item.style.maxHeight =
                    item.getBoundingClientRect().height + 'px'
            }

            if (!initialHeight)
                item.setAttribute(
                    'initial-height',
                    item.getBoundingClientRect().height + 'px'
                )
        })
    }

    hideCategoryPage(e) {
        const page = this.$('.category-page.open')

        const slug = page.getAttribute('slug')

        const category = this.$(`.menu-category[slug="${slug}"]`)

        page.classList.add('closing')

        this.bindPageToCategoryBoundary(page, category)

        page.style.borderTopLeftRadius = '2rem'
        page.style.borderTopRightRadius = '2rem'

        const onTransitionEnd = () => {
            page.classList.remove('open')
            page.classList.remove('animated')
            page.classList.remove('closing')
            page.removeEventListener('transitionend', onTransitionEnd)
        }

        page.addEventListener('transitionend', onTransitionEnd)
    }

    bindPageToCategoryBoundary(page, category) {
        page.style.top = `${category.getBoundingClientRect().top}px`

        page.style.bottom = `calc(100vh - ${
            category.getBoundingClientRect().bottom
        }px + 2rem)`
    }

    showAllCategories() {
        for (const c of this.$$('.menu-category')) {
            c.classList.remove('hidden')
            c.classList.remove('selected')
        }
    }

    hideOtherCategories(category) {
        for (const c of this.$$('.menu-category')) {
            c.classList.add('hidden')
            c.classList.remove('selected')
        }

        category.classList.add('selected')
        category.classList.remove('hidden')
    }

    openCategoryPage(category) {
        const slug = category.getAttribute('slug')

        const selector = `.category-page[slug="${slug}"]`

        const categoryPage = this.$(selector)

        categoryPage.classList.add('open')

        categoryPage.style.zIndex = getComputedStyle(category).zIndex

        categoryPage.style.backgroundColor =
            getComputedStyle(category).backgroundColor

        categoryPage.classList.remove('sub-category-active')

        const categoryPageTitle = categoryPage.querySelector(
            '.category-page-title'
        )

        categoryPageTitle.style.color = getComputedStyle(category).color

        this.bindPageToCategoryBoundary(categoryPage, category)

        setTimeout(() => {
            categoryPage.classList.add('animated')
            categoryPage.style.top = 0
            categoryPage.style.bottom = 0
            categoryPage.style.borderTopRightRadius = 0
            categoryPage.style.borderTopLeftRadius = 0
        }, 50)

        this.openCategoryPagePromise = new Promise((resolve) => {
            const onTransitionEnd = () => {
                resolve()

                categoryPage.removeEventListener(
                    'transitionend',
                    onTransitionEnd
                )
            }

            categoryPage.addEventListener('transitionend', onTransitionEnd)
        })

        document.dispatchEvent(
            new CustomEvent('category-page:open', {
                detail: {
                    categoryPage,
                    promise: this.openCategoryPagePromise,
                },
            })
        )
    }

    restoreScroll() {
        window.scrollTo(0, this.lastWindowScrollTop)
    }

    saveScrollPosition() {
        this.lastWindowScrollTop = window.scrollY
    }

    scrollToTop() {
        window.scrollTo(0, 0)
    }

    unblockBodyScroll() {
        document.body.classList.remove('block-scroll')
    }

    blockBodyScroll() {
        document.body.classList.add('block-scroll')
    }

    async onMenuCategoryClick(e) {
        const category = e.target

        this.hideOtherCategories(category)

        this.saveScrollPosition()

        this.openCategoryPage(category)

        this.bindPagesToGeneratedLayoutWidth()

        await this.openCategoryPagePromise

        this.scrollToTop()

        this.blockBodyScroll()
    }

    onMenuCategoryClose(e) {
        this.unblockBodyScroll()
        this.restoreScroll()

        setTimeout(() => {
            this.hideCategoryPage(e)
            this.showAllCategories()
        }, 10)
    }

    bindPagesToGeneratedLayoutWidth = () => {
        this.$$('.category-page').forEach((page) => {
            const parent = page.closest('.layout-generated-webpage')

            const { left, width, right } = parent.getBoundingClientRect()

            page.style.left = `${left}px`
            page.style.width = `${width}px`
            page.style.right = `calc(100vw - ${right}px)`
        })
    }

    scrollToBottom(duration, shouldStop) {
        // When should we finish?
        var finishAt = Date.now() + duration

        // Start
        requestAnimationFrame(tick)

        const animationElement = document.documentElement

        function tick() {
            if (shouldStop()) {
                return
            }

            // How many frames left? (60fps = 16.6ms per frame)
            var framesLeft = (finishAt - Date.now()) / 16.6

            // How far do we have to go?
            var distance =
                document.body.offsetHeight -
                (window.innerHeight + window.scrollY)

            if (distance <= 0) {
                // Done (this shouldn't happen, belt & braces)
                return
            }

            // Adjust by one frame's worth
            if (framesLeft <= 1) {
                // Last call
                animationElement.scrollTop += distance
            } else {
                // Not the last, adjust and schedule next
                animationElement.scrollTop += Math.max(1, distance / framesLeft)

                requestAnimationFrame(tick)
            }
        }
    }

    async autoScrollDown() {
        await new Promise((r) => setTimeout(r, 1500))

        let scrollingUp = false,
            touched = false,
            clicked = false

        this.scrollToBottom(5000, () => {
            return scrollingUp || clicked || touched
        })

        const onWindowScroll = () => {
            if (this.autoScrollDown_lastWindowScroll > window.scrollY) {
                scrollingUp = true
            }

            this.autoScrollDown_lastWindowScroll = window.scrollY
        }

        this.autoScrollDown_lastWindowScroll = 0

        window.addEventListener('scroll', onWindowScroll)

        window.addEventListener('click', () => {
            clicked = true
        })

        window.addEventListener('touchstart', () => {
            touched = true
        })
    }

    onDomContentLoaded() {
        if (!this.shouldRender()) return

        window.addEventListener('resize', this.bindPagesToGeneratedLayoutWidth)

        setTimeout(this.bindPagesToGeneratedLayoutWidth, 100)

        document.addEventListener('all-images-loaded', () =>
            this.autoScrollDown()
        )

        document.addEventListener('category-page:open', this.onCategoryPageOpen)
    }
}
