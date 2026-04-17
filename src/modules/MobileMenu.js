class MobileMenu {
  constructor() {
    this.menu = document.querySelector(".site-header__menu")
    this.openButton = document.querySelector(".site-header__menu-trigger")

    if (this.menu && this.openButton) {
      this.events()
    }
  }

  events() {
    this.openButton.addEventListener("click", () => this.openMenu())
  }

  openMenu() {
    if (!this.menu || !this.openButton) {
      return
    }

    this.openButton.classList.toggle("fa-bars")
    this.openButton.classList.toggle("fa-window-close")
    this.menu.classList.toggle("site-header__menu--active")
  }
}

export default MobileMenu
