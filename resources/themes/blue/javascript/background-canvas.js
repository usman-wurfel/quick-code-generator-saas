// import * as THREE from 'three'

class BackgroundCanvas {
    scene
    renderer
    camera
    startTime
    material
    shader

    resolution

    static _load() {
        const instance = new BackgroundCanvas()

        instance.init()
    }

    static shouldLoad() {
        const background = document.querySelector(
            '.website-banner div.background:not(.user-background)'
        )

        return !!background
    }

    static load() {
        if (!this.shouldLoad()) return

        const instance = new BackgroundCanvas()

        const scriptPath = '/assets/lib/three.min.js'

        const script = document.createElement('script')

        script.onload = () => {
            instance.init()
        }

        script.src = scriptPath

        document.head.appendChild(script)
    }

    rgbaVec4(rgbaString) {
        const [r, g, b, a] = rgbaString
            .replace('#', '')
            .match(/../g)
            .map((n) => parseInt(n, 16) / 255)

        return new THREE.Vector4(r, g, b, a)
    }

    insertCanvas(canvas) {
        canvas.classList.add('background')

        let parent = document.querySelector('.website-banner')

        for (let i = 0; i < 1; i++) {
            let div = document.createElement('div')

            div.classList.add('c-group')

            parent.appendChild(div)

            parent = div
        }

        parent.appendChild(canvas)

        document.querySelector('.website-banner div.background')?.remove()
    }

    start() {
        this.startTime = new Date()

        this.scene = new THREE.Scene()
        //const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        this.camera = new THREE.OrthographicCamera(
            -0.5,
            0.5,
            0.5,
            -0.5,
            0.1,
            1000
        )

        const { width, height } = this.initialSize()

        this.renderer = new THREE.WebGLRenderer()

        this.renderer.setSize(width, height)

        this.insertCanvas(this.renderer.domElement)

        // background — IT tech ink
        const darkBlue = '#070b14ff'

        // cyan gradient (top right)
        const greenGradientColor1 = '#2ec9b0ff'
        const greenGradientColor2 = '#2ec9b000'

        // signal orange gradient (bottom left)
        const blueGradientColor1 = '#ff5a1fff'
        const blueGradientColor2 = '#ff5a1f00'

        const uniforms = {
            time: { type: 'float', value: 0 },
            resolution: {
                type: 'vec2',
                // value: new THREE.Vector2(window.innerWidth, window.innerHeight),
                value: this.resolution,
            },
            backgroundColor: {
                type: 'vec4',
                value: this.rgbaVec4(darkBlue),
            },

            topRightCircleRadius: {
                type: 'float',
                value: 0.72,
            },
            topRightCircleScale: {
                type: 'vec2',
                value: new THREE.Vector2(0.35, 0.35),
            },
            topRightCircleColor1: {
                type: 'vec4',
                value: this.rgbaVec4(greenGradientColor1),
            },
            topRightCircleColor2: {
                type: 'vec4',
                value: this.rgbaVec4(greenGradientColor2),
            },

            bottomLeftCircleRadius: {
                type: 'float',
                value: 0.78,
            },
            bottomLeftCircleScale: {
                type: 'vec2',
                value: new THREE.Vector2(0.38, 0.38),
            },
            bottomLeftCircleColor1: {
                type: 'vec4',
                value: this.rgbaVec4(blueGradientColor1),
            },
            bottomLeftCircleColor2: {
                type: 'vec4',
                value: this.rgbaVec4(blueGradientColor2),
            },
        }

        this.material = new THREE.ShaderMaterial({
            uniforms: uniforms,
            vertexShader: this.vertexShader(),
            fragmentShader: this.fragmentShader2(),
        })

        const planeGeometry = new THREE.PlaneGeometry(1, 1, 1)
        const plane = new THREE.Mesh(planeGeometry, this.material)

        this.scene.add(plane)
        this.camera.position.z = 14
    }

    animate = () => {
        var seconds = (new Date() - this.startTime) / 1000

        this.material.uniforms.time.value = seconds

        this.material.uniforms.resolution.value = this.resolution

        requestAnimationFrame(this.animate)

        this.renderer.render(this.scene, this.camera)
    }

    init() {
        window.addEventListener('resize', () => this.onWindowResize())

        this.syncResolution()

        this.start()
        this.animate()
    }

    syncResolution() {
        const { width, height } = document
            .querySelector('.website-banner')
            .getBoundingClientRect()

        if (!this.resolution) {
            this.resolution = new THREE.Vector2()
        }

        if (!this.resolutionInitDone) {
            this.resolution.x = width
            this.resolution.y = height

            this.resolutionInitDone = true
        }

        clearTimeout(this.resizeTimeout)

        this.resizeTimeout = setTimeout(() => {
            this.resolution.x = width
            this.resolution.y = height
            this.renderer?.setSize(width, height)
        }, 500)
    }

    onWindowResize() {
        this.syncResolution()
    }

    initialSize() {
        const width = 1680
        const height = 868

        return { width, height }
    }

    vertexShader() {
        return `
            varying vec3 vUV;
    
            void main() 
            {
                vUV = position;
    
                vec4 modelViewPosition = modelViewMatrix * vec4(position, 1.0);
                gl_Position = projectionMatrix * modelViewPosition;
            }
        `
    }

    fragmentShader2() {
        return `
            varying vec3 vUV;

            uniform float time;
            uniform vec2 resolution;

            uniform vec4 backgroundColor;

            uniform float topRightCircleRadius;
            uniform vec2 topRightCircleScale;
            uniform vec4 topRightCircleColor1;
            uniform vec4 topRightCircleColor2;

            uniform float bottomLeftCircleRadius;
            uniform vec2 bottomLeftCircleScale;
            uniform vec4 bottomLeftCircleColor1;
            uniform vec4 bottomLeftCircleColor2;

            float hash21(vec2 co){
                return fract(sin(dot(co, vec2(12.9898, 78.233))) * 43758.5453);
            }
    
            vec2 hash22(vec2 co){
                float x = hash21(co);
                float y = hash21(vec2(x, co.y));
                return vec2(x, y);
            }

            vec4 Background(vec2 uv)
            {                
                return backgroundColor;
            }

            vec4 Circle(vec2 uv, vec2 scale, float r, vec4 color1, vec4 color2)
            {
                uv /= scale;
                float d = smoothstep(r * 0.3, r, length(uv));
                
                return mix(color1, color2, d);
            }

            float FrameCircleDist(vec2 uv, float r)
            {
                return abs(length(uv) - r);
            }

            vec4 FrameCircle(vec2 uv, float r, float thickness, float angle)
            {
                float m = FrameCircleDist(uv, r);
                m = smoothstep(thickness, thickness - 0.002, m);
                float theta = angle;
                float d = m * dot(vec2(sin(theta), cos(theta)), uv);     


                return vec4(m, m, m, d);
            }

            vec4 Blend(vec4 col1, vec4 col2)
            {
                return vec4(mix(vec3(col1.xyz), vec3(col2.xyz), col2.w * 0.6), 1.);
            }

            float CirclePoolMask(vec2 uv, float size, float speed)
            {
                uv *= size;
                vec2 id = floor(uv);
                uv = fract(uv) - 0.5;
    
                vec2 r = hash22(id);

                float d = 0.;
    
                if (fract(r.x + r.y) < 0.4)
                {
                    d = length(uv - sin(r * time * 0.1 * speed) * 0.35);
                    d = smoothstep(0.08, 0.05, d);                 
                }

                return d;
            }

            void main()
            {
                vec2 uv = vUV.xy;
                uv.x *= resolution.x / resolution.y;

                vec4 col = vec4(0.);
                col = Background(uv);
                vec4 circle1 = Circle(uv - vec2(0.7, 0.5), vec2(1.45, 1.), 0.85, topRightCircleColor1, topRightCircleColor2);
                circle1 *= (sin(time) * 0.5 + 0.5) * 0.2 + 0.8;
                col = Blend(col, circle1);
                vec4 circle2 = Circle(uv - vec2(-1.1, -0.8), vec2(1.), 1.08, bottomLeftCircleColor1, bottomLeftCircleColor2);
                circle2 *= (sin(time + 3.) * 0.5 + 0.5) * 0.2 + 0.8;
                col = Blend(col, circle2);

                vec4 frameCircle1 = FrameCircle(uv / vec2(1.15, 1.) - vec2(0.42, 0.53), 0.28, 0.004, 3.4);
                col = Blend(col, frameCircle1);

                vec4 frameCircle2 = FrameCircle(uv / vec2(1.1, 1.1) - vec2(-0.83, -0.3), 0.23, 0.004, 0.8);
                col = Blend(col, frameCircle2);

                float mask = CirclePoolMask(uv, 5., 3.);
                vec4 poolColor = vec4(vec3(mask), 0.4);
                col = Blend(col, poolColor);
                
                gl_FragColor = col;
            }
        `
    }

    fragmentShader() {
        return `
    
            varying vec3 vUV;
            uniform float time;
            uniform vec2 resolution;
    
            uniform vec4 color1;
            uniform vec4 color2;
            uniform vec4 color3;
    
            float hash21(vec2 co){
                return fract(sin(dot(co, vec2(12.9898, 78.233))) * 43758.5453);
            }
    
            vec2 hash22(vec2 co){
                float x = hash21(co);
                float y = hash21(vec2(x, co.y));
                return vec2(x, y);
            }

            
    
            void main()
            {
                vec2 uv = vec2(vUV.x, vUV.y);
                uv.x *= resolution.x / resolution.y;
                
                // uv.y += 0.1 * sin(uv.x * 10. + time * 1.);
               
                vec3 col = color1;
    
                float d = length(uv - vec2(0.9, 0.5)) + (sin(time * 2.) * 0.5 + 0.5) * 0.2;
    
                col = mix(color2, col, d);
                
                d = length(uv - vec2(-0.9, -0.5)) + (sin(time * 2.) * 0.5 + 0.5) * 0.2;
                
                col = mix(color3, col, d);
    
                d = length(uv - vec2(0.4, 0.6));
    
                uv *= 5.;
                vec2 id = floor(uv);
                uv = fract(uv) - 0.5;
    
                vec2 r = hash22(id);
    
                if (fract(r.x + r.y) < 0.2)
                {
                    d = length(uv - sin(r * time * 0.2) * 0.35);
                    d = smoothstep(0.08, 0.05, d);
    
                    col = mix(col, vec3(d), d * 0.4);
                }
    
                gl_FragColor = vec4(col.x, col.y, col.z, 1);
            }
        `
    }
}

BackgroundCanvas.load()
