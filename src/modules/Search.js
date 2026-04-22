import $ from "jquery"

class Search {
  constructor() {
    this.openButtons = $(".js-search-trigger")
    this.isOverlayOpen = false
    this.typingTimer = null
    this.isSpinnerVisible = false
    this.previousValue = ""
    this.currentRequestId = 0
    this.rootURL = typeof universityData !== "undefined" ? universityData.root_url : window.location.origin

    this.addSearchHTML()
    this.refreshSearchElements()
    this.events()
  }

  refreshSearchElements() {
    this.resultsDiv = $("#search-overlay__results")
    this.closeButton = $(".search-overlay__close")
    this.searchOverlay = $(".search-overlay")
    this.searchField = $("#search-term")
  }

  events() {
    this.openButtons.on("click", this.openOverlay.bind(this))
    this.closeButton.on("click", this.closeOverlay.bind(this))
    $(document).on("keydown", this.keyPressDispatcher.bind(this))
    this.searchField.on("keyup", this.typingLogic.bind(this))
  }

  typingLogic() {
    const searchValue = this.searchField.val().trim()

    if (searchValue === this.previousValue) {
      return
    }

    clearTimeout(this.typingTimer)

    if (searchValue) {
      if (!this.isSpinnerVisible) {
        this.resultsDiv.html('<div class="spinner-loader"></div>')
        this.isSpinnerVisible = true
      }

      const requestId = ++this.currentRequestId
      this.typingTimer = setTimeout(this.getResults.bind(this, searchValue, requestId), 750)
    } else {
      this.resultsDiv.html("")
      this.isSpinnerVisible = false
      this.currentRequestId++
    }

    this.previousValue = searchValue
  }

  getResults(searchValue, requestId) {
    fetch(`${this.rootURL}/wp-json/university/v1/search?term=${encodeURIComponent(searchValue)}`)
      .then(response => response.json())
      .then(results => {
        if (requestId !== this.currentRequestId || !this.isOverlayOpen) {
          return
        }

        this.resultsDiv.html(this.createResultsHtml(results))
        this.isSpinnerVisible = false
      })
      .catch(() => {
        if (requestId !== this.currentRequestId || !this.isOverlayOpen) {
          return
        }

        this.resultsDiv.html("<p>Unexpected error; please try again.</p>")
        this.isSpinnerVisible = false
      })
  }

  escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, char => {
      const entities = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
      }

      return entities[char]
    })
  }

  createAuthorByline(authorName) {
    if (!authorName) {
      return ""
    }

    return ` <span class="search-overlay__result-author">by ${this.escapeHtml(authorName)}</span>`
  }

  getDefaultResultGroups() {
    return {
      generalInfo: [],
      programs: [],
      professors: [],
      campuses: [],
      events: []
    }
  }

  getResultGroupName(result) {
    const resultType = String(result.type || "").toLowerCase()
    const resultTitle = String(result.title || "").toLowerCase()

    if (resultType === "program" || (resultType === "archive" && resultTitle.includes("program"))) {
      return "programs"
    }

    if (resultType === "professor" || (resultType === "archive" && resultTitle.includes("professor"))) {
      return "professors"
    }

    if (resultType === "campus" || (resultType === "archive" && resultTitle.includes("campus"))) {
      return "campuses"
    }

    if (resultType === "event" || (resultType === "archive" && resultTitle.includes("event"))) {
      return "events"
    }

    return "generalInfo"
  }

  groupResults(results) {
    return results.reduce((groups, result) => {
      const groupName = this.getResultGroupName(result)
      groups[groupName].push(result)
      return groups
    }, this.getDefaultResultGroups())
  }

  createResultList(results, emptyMessage = "") {
    if (!results.length) {
      return emptyMessage ? `<p class="search-overlay__section-message">${emptyMessage}</p>` : ""
    }

    return `
      <ul class="link-list min-list">
        ${results
          .map(
            result => `
              <li>
                <a href="${this.escapeHtml(result.url)}">${this.escapeHtml(result.title)}</a>${this.createAuthorByline(result.authorName)}
              </li>
            `
          )
          .join("")}
      </ul>
    `
  }

  createEmptySectionMessage(title) {
    const normalizedTitle = String(title || "").toLowerCase()

    if (normalizedTitle === "general information") {
      return "No general information matches that search."
    }

    return `No ${normalizedTitle} match that search.`
  }

  createSectionHtml(title, results, emptyMessage = "") {
    const fallbackMessage = emptyMessage || this.createEmptySectionMessage(title)

    return `
      <div class="search-overlay__section">
        <h2 class="search-overlay__section-title">${title}</h2>
        ${this.createResultList(results, fallbackMessage)}
      </div>
    `
  }

  createResultsHtml(results) {
    const normalizedResults = Array.isArray(results) ? results : []
    const groupedResults = this.groupResults(normalizedResults)

    return `
      <div class="row search-overlay__results-layout">
        <div class="one-third search-overlay__column">
          ${this.createSectionHtml("General Information", groupedResults.generalInfo)}
        </div>
        <div class="one-third search-overlay__column">
          ${this.createSectionHtml("Programs", groupedResults.programs)}
          ${this.createSectionHtml("Professors", groupedResults.professors)}
        </div>
        <div class="one-third search-overlay__column">
          ${this.createSectionHtml("Campuses", groupedResults.campuses)}
          ${this.createSectionHtml("Events", groupedResults.events)}
        </div>
      </div>
    `
  }

  keyPressDispatcher(e) {
    if (e.key === "Escape" && this.isOverlayOpen) {
      this.closeOverlay()
    }

    if (
      (e.key === "s" || e.key === "S") &&
      !this.isOverlayOpen &&
      !e.ctrlKey &&
      !e.altKey &&
      !e.metaKey &&
      !this.typingInInput()
    ) {
      this.openOverlay(e)
    }
  }

  typingInInput() {
    const activeElement = document.activeElement

    if (!activeElement) {
      return false
    }

    const tagName = activeElement.tagName

    return (
      tagName === "INPUT" ||
      tagName === "TEXTAREA" ||
      tagName === "SELECT" ||
      activeElement.isContentEditable
    )
  }

  openOverlay(e) {
    if (e) {
      e.preventDefault()
    }
    
    this.searchOverlay.addClass("search-overlay--active")
    $("body").addClass("body-no-scroll")
    this.isOverlayOpen = true
    this.searchField.val("");
    setTimeout(() => this.searchField.trigger("focus"), 301);
    if (this.searchField.length) {
      this.searchField.trigger("focus")
    }
  }

  closeOverlay(e) {
    if (e) {
      e.preventDefault()
    }

    clearTimeout(this.typingTimer)
    this.currentRequestId++
    this.searchField.val("")
    this.resultsDiv.html("")
    this.isSpinnerVisible = false
    this.previousValue = ""
    this.searchOverlay.removeClass("search-overlay--active")
    $("body").removeClass("body-no-scroll")
    this.isOverlayOpen = false
  }

  addSearchHTML() {
    if ($(".search-overlay").length) {
      return
    }

    $("body").append(`
     <div class="search-overlay">
    <div class="search-overlay__top">
      <div class="container">
        <i class="fa fa-search search-overlay__icon" aria-hidden="true"></i>
          <input type="text" class="search-term" placeholder="What are you looking for?" id="search-term" autocomplete="off">
        <button type="button" class="search-overlay__close" aria-label="Close search">
          <i class="fa fa-window-close" aria-hidden="true"></i>
        </button>
      </div>
    </div>
    <div class="container" >
      <div id="search-overlay__results">

      </div>
    </div>
  </div>
    `)
  }
}

export default Search
