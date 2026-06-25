name: Feature Request
description: Propose a new feature or enhancement
title: "feat: [Short description of the feature request]"
labels: ["enhancement"]
body:
  - type: markdown
    attributes:
      value: |
        Have an idea to make this system better? Share it here!
  - type: textarea
    id: feature-description
    attributes:
      label: Is your feature request related to a problem?
      description: A clear and concise description of what the problem/limitation is.
    validations:
      required: true
  - type: textarea
    id: proposed-solution
    attributes:
      label: Describe the solution you'd like
      description: A clear and concise description of what you want to happen.
    validations:
      required: true
  - type: textarea
    id: alternatives
    attributes:
      label: Describe alternatives you've considered
      description: A clear and concise description of any alternative solutions or features you've considered.
    validations:
      required: false
  - type: textarea
    id: context
    attributes:
      label: Additional Context
      description: Add any other context or screenshots about the feature request here.
    validations:
      required: false
